<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\Respondent;
use App\Models\RespondentDocument;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Support\DashboardBenchmark;
use App\Support\DashboardFactBuilder;
use App\Support\DashboardHealthCheck;
use App\Support\SecureUploadStorage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dashboard:rebuild-facts {--chunk=500}', function (DashboardFactBuilder $builder) {
    $chunk = max(50, (int) $this->option('chunk'));
    $total = SurveyResponse::query()->count();

    $this->info("Rebuilding dashboard facts for {$total} survey responses using chunk size {$chunk}...");
    $bar = $this->output->createProgressBar($total);
    $bar->start();

    $startedAt = microtime(true);
    $count = $builder->rebuildAll($chunk, function (int $processed) use ($bar): void {
        $bar->advance($processed);
    });
    $bar->finish();
    $this->newLine(2);

    $this->info('Dashboard facts rebuilt for '.$count.' survey responses in '.round(microtime(true) - $startedAt, 2).' seconds.');
})->purpose('Rebuild normalized dashboard answer facts from survey responses');

Artisan::command('dashboard:health {--fail-on-warning}', function (DashboardHealthCheck $healthCheck) {
    $report = $healthCheck->run();

    $this->info('Dashboard production health: '.strtoupper($report['status']));
    $this->newLine();

    $this->table(
        ['Metric', 'Value'],
        collect($report['summary'])
            ->map(fn ($value, $metric): array => [$metric, (string) $value])
            ->values()
            ->all()
    );

    $this->table(
        ['Status', 'Check', 'Detail'],
        collect($report['checks'])
            ->map(fn (array $check): array => [
                strtoupper($check['status']),
                $check['name'],
                $check['detail'],
            ])
            ->all()
    );

    if ($report['status'] !== 'ok' && $this->option('fail-on-warning')) {
        return 1;
    }

    return 0;
})->purpose('Check dashboard facts, queue backlog, and production analytics health');

Artisan::command('dashboard:benchmark {--iterations=3} {--rebuild} {--chunk=500}', function (DashboardBenchmark $benchmark) {
    $user = User::role('administrator')->first() ?? User::query()->first();

    if (! $user) {
        $this->error('No user found for benchmark. Seed users first.');

        return 1;
    }

    $report = $benchmark->run(
        $user,
        (int) $this->option('iterations'),
        (bool) $this->option('rebuild'),
        max(50, (int) $this->option('chunk')),
    );

    $this->info('Dashboard benchmark');
    $this->line('User: '.$report['user']['email'].' ('.$report['user']['role'].')');
    $this->newLine();

    $this->table(
        ['Dataset', 'Count'],
        collect($report['dataset'])
            ->map(fn ($value, $key): array => [$key, number_format((int) $value)])
            ->values()
            ->all()
    );

    $rows = [];
    foreach ($report['timings'] as $metric => $value) {
        if ($value === null) {
            continue;
        }

        $rows[] = is_array($value)
            ? [$metric, $value['min'], $value['avg'], $value['max']]
            : [$metric, '-', round($value, 4), '-'];
    }

    $this->table(['Metric', 'Min (s)', 'Avg (s)', 'Max (s)'], $rows);

    return 0;
})->purpose('Benchmark dashboard stats, analytics facts, health checks, and optional fact rebuild');

Artisan::command('uploads:prune-orphans {--delete : Delete orphan files instead of reporting only} {--legacy : Also scan the legacy public upload disk}', function (SecureUploadStorage $secureStorage) {
    $delete = (bool) $this->option('delete');
    $scanLegacy = (bool) $this->option('legacy');
    $allowedPrefixes = ['documents', 'photos'];
    $referencedPaths = [];

    Respondent::query()
        ->whereNotNull('photo_path')
        ->select(['id', 'photo_path'])
        ->chunkById(1000, function ($respondents) use (&$referencedPaths): void {
            foreach ($respondents as $respondent) {
                $referencedPaths[$respondent->photo_path] = true;
            }
        });

    RespondentDocument::query()
        ->whereNotNull('file_path')
        ->select(['id', 'file_path'])
        ->chunkById(1000, function ($documents) use (&$referencedPaths): void {
            foreach ($documents as $document) {
                $referencedPaths[$document->file_path] = true;
            }
        });

    $privateDisk = config('uploads.private_disk');
    $legacyDisk = config('uploads.legacy_public_disk');
    $diskNames = [$privateDisk => 'private'];

    if ($scanLegacy) {
        $diskNames[$legacyDisk] = 'legacy-public';
    }

    $orphanFiles = [];
    $deletedCount = 0;

    foreach ($diskNames as $disk => $label) {
        foreach ($allowedPrefixes as $prefix) {
            foreach (Storage::disk($disk)->allFiles($prefix) as $path) {
                if (! $secureStorage->validPrivatePath($path, $allowedPrefixes)) {
                    continue;
                }

                if (isset($referencedPaths[$path])) {
                    continue;
                }

                $orphanFiles[] = [$label, $path];

                if ($delete && Storage::disk($disk)->delete($path)) {
                    $deletedCount++;
                }
            }
        }
    }

    $missingFiles = [];

    foreach (array_keys($referencedPaths) as $path) {
        if (! $secureStorage->validPrivatePath($path, $allowedPrefixes)) {
            $missingFiles[] = ['unsafe-reference', $path];

            continue;
        }

        if (! $secureStorage->exists($path, $allowedPrefixes)) {
            $missingFiles[] = ['missing-file', $path];
        }
    }

    $this->info('Upload storage audit');
    $this->table(['Metric', 'Value'], [
        ['Referenced DB paths', number_format(count($referencedPaths))],
        ['Orphan files found', number_format(count($orphanFiles))],
        ['Deleted orphan files', number_format($deletedCount)],
        ['Missing/unsafe DB references', number_format(count($missingFiles))],
        ['Mode', $delete ? 'delete' : 'dry-run'],
    ]);

    if ($orphanFiles) {
        $this->warn('First orphan files:');
        $this->table(['Disk', 'Path'], array_slice($orphanFiles, 0, 20));
    }

    if ($missingFiles) {
        $this->warn('First missing or unsafe DB references:');
        $this->table(['Issue', 'Path'], array_slice($missingFiles, 0, 20));
    }

    if (! $delete && $orphanFiles) {
        $this->line('Run with --delete after reviewing the list to remove orphan files.');
    }

    return $missingFiles ? 1 : 0;
})->purpose('Audit and optionally delete unreferenced private upload files');
