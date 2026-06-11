<?php

use App\Http\Controllers\App\DashboardController;
use App\Models\ExportFile;
use App\Models\Respondent;
use App\Models\RespondentDocument;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Support\DashboardBenchmark;
use App\Support\DashboardCache;
use App\Support\DashboardFactBuilder;
use App\Support\DashboardFactReader;
use App\Support\DashboardHealthCheck;
use App\Support\SecureUploadStorage;
use App\Support\SurveyResponseAccess;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

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

Artisan::command('app:backup-database {--connection= : Database connection name} {--keep= : Number of latest backups to keep}', function () {
    $connectionName = $this->option('connection') ?: config('database.default');
    $connection = config("database.connections.{$connectionName}");

    if (! is_array($connection)) {
        $this->error("Database connection [{$connectionName}] is not configured.");

        return 1;
    }

    $diskName = config('backup.database.disk');
    $backupPath = trim((string) config('backup.database.path'), '/');
    $keepLatest = max(1, (int) ($this->option('keep') ?: config('backup.database.keep_latest')));
    $disk = Storage::disk($diskName);

    if (! method_exists($disk, 'path')) {
        $this->error("Backup disk [{$diskName}] must support local filesystem paths.");

        return 1;
    }

    $directory = $disk->path($backupPath);
    File::ensureDirectoryExists($directory);

    $driver = $connection['driver'] ?? null;
    $database = $connection['database'] ?? null;
    $timestamp = now()->format('Ymd_His');
    $baseName = "database_{$connectionName}_{$timestamp}";

    $this->info("Creating {$driver} database backup for connection [{$connectionName}]...");

    if ($driver === 'sqlite') {
        if (! is_string($database) || $database === ':memory:' || $database === '') {
            $this->error('SQLite in-memory databases cannot be backed up to a file.');

            return 1;
        }

        $databasePath = realpath($database) ?: $database;

        if (! is_file($databasePath)) {
            $this->error("SQLite database file was not found: {$databasePath}");

            return 1;
        }

        $target = $directory.DIRECTORY_SEPARATOR.$baseName.'.sqlite';

        if (! File::copy($databasePath, $target)) {
            $this->error("Failed to copy SQLite database to {$target}");

            return 1;
        }
    } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
        if (! is_string($database) || $database === '') {
            $this->error("Database name is required for connection [{$connectionName}].");

            return 1;
        }

        $target = $directory.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $process = new Process([
            (string) config('backup.database.mysql_dump_binary'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 3306),
            '--user='.(string) ($connection['username'] ?? ''),
            (string) $database,
        ]);

        if (! empty($connection['password'])) {
            $process->setEnv(['MYSQL_PWD' => (string) $connection['password']]);
        }

        $process->setTimeout(3600);
        File::put($target, '');
        $process->run(function (string $type, string $buffer) use ($target): void {
            if ($type === Process::OUT) {
                File::append($target, $buffer);
            }
        });

        if (! $process->isSuccessful()) {
            File::delete($target);
            $this->error(trim($process->getErrorOutput()) ?: 'mysqldump failed.');

            return 1;
        }
    } elseif ($driver === 'pgsql') {
        if (! is_string($database) || $database === '') {
            $this->error("Database name is required for connection [{$connectionName}].");

            return 1;
        }

        $target = $directory.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $process = new Process([
            (string) config('backup.database.pg_dump_binary'),
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 5432),
            '--username='.(string) ($connection['username'] ?? ''),
            (string) $database,
        ]);

        if (! empty($connection['password'])) {
            $process->setEnv(['PGPASSWORD' => (string) $connection['password']]);
        }

        $process->setTimeout(3600);
        File::put($target, '');
        $process->run(function (string $type, string $buffer) use ($target): void {
            if ($type === Process::OUT) {
                File::append($target, $buffer);
            }
        });

        if (! $process->isSuccessful()) {
            File::delete($target);
            $this->error(trim($process->getErrorOutput()) ?: 'pg_dump failed.');

            return 1;
        }
    } else {
        $this->error("Database driver [{$driver}] is not supported by app:backup-database.");

        return 1;
    }

    $files = collect(File::files($directory))
        ->filter(fn ($file): bool => str_starts_with($file->getFilename(), "database_{$connectionName}_"))
        ->sortByDesc(fn ($file): int => $file->getMTime())
        ->values();

    $files->slice($keepLatest)->each(fn ($file) => File::delete($file->getPathname()));

    $relativeTarget = $backupPath.'/'.basename($target);

    $this->info("Backup saved: {$relativeTarget}");
    $this->line('Retained latest backups: '.$keepLatest);

    return 0;
})->purpose('Create a local database backup and prune old backup files');

Artisan::command('app:production-status {--fail-on-warning}', function (DashboardHealthCheck $healthCheck) {
    $dashboardReport = $healthCheck->run();
    $backupDisk = config('backup.database.disk');
    $backupPath = trim((string) config('backup.database.path'), '/');
    $maxBackupAgeHours = max(1, (int) config('backup.database.max_age_hours'));
    $backupStatus = 'warning';
    $backupDetail = 'no database backup found';
    $latestBackup = null;

    try {
        $disk = Storage::disk($backupDisk);

        if (method_exists($disk, 'path')) {
            $directory = $disk->path($backupPath);
            $latestBackup = is_dir($directory)
                ? collect(File::files($directory))
                    ->filter(fn ($file): bool => str_starts_with($file->getFilename(), 'database_'))
                    ->sortByDesc(fn ($file): int => $file->getMTime())
                    ->first()
                : null;

            if ($latestBackup) {
                $ageHours = (int) floor(max(0, time() - $latestBackup->getMTime()) / 3600);
                $backupStatus = $ageHours <= $maxBackupAgeHours ? 'ok' : 'warning';
                $backupDetail = $latestBackup->getFilename().' ('.$ageHours.'h old, '.number_format($latestBackup->getSize()).' bytes)';
            }
        } else {
            $backupDetail = "backup disk [{$backupDisk}] does not expose local paths";
        }
    } catch (Throwable $e) {
        $backupDetail = 'backup check failed: '.$e->getMessage();
    }

    $checks = collect($dashboardReport['checks'])
        ->map(fn (array $check): array => [
            'status' => $check['status'],
            'name' => 'dashboard: '.$check['name'],
            'detail' => $check['detail'],
        ])
        ->push([
            'status' => $backupStatus,
            'name' => 'database backup freshness',
            'detail' => $backupDetail,
        ]);

    $status = $checks->contains(fn (array $check): bool => $check['status'] !== 'ok') ? 'warning' : 'ok';

    $this->info('Production status: '.strtoupper($status));
    $this->newLine();

    $this->table(['Metric', 'Value'], [
        ['Environment', app()->environment()],
        ['Queue connection', config('queue.default')],
        ['Cache store', config('cache.default')],
        ['Backup max age', $maxBackupAgeHours.' hours'],
        ['Dashboard status', strtoupper($dashboardReport['status'])],
    ]);

    $this->table(
        ['Status', 'Check', 'Detail'],
        $checks
            ->map(fn (array $check): array => [
                strtoupper($check['status']),
                $check['name'],
                $check['detail'],
            ])
            ->values()
            ->all()
    );

    if ($status !== 'ok' && $this->option('fail-on-warning')) {
        return 1;
    }

    return 0;
})->purpose('Report production dashboard, queue, and backup readiness status');

Artisan::command('exports:prune {--delete : Delete expired files and mark export records expired} {--grace-hours= : Extra hours after expiry before pruning}', function () {
    $delete = (bool) $this->option('delete');
    $graceHours = max(0, (int) ($this->option('grace-hours') ?? config('exports.cleanup_grace_hours', 0)));
    $expiredBefore = now()->subHours($graceHours);
    $matched = 0;
    $deleted = 0;

    ExportFile::query()
        ->where('status', ExportFile::STATUS_READY)
        ->whereNull('file_deleted_at')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', $expiredBefore)
        ->orderBy('id')
        ->chunkById(100, function ($exports) use ($delete, &$matched, &$deleted): void {
            foreach ($exports as $exportFile) {
                $matched++;

                if (! $delete) {
                    continue;
                }

                $removed = ! Storage::disk($exportFile->disk)->exists($exportFile->path)
                    || Storage::disk($exportFile->disk)->delete($exportFile->path);

                if ($removed) {
                    $exportFile->forceFill([
                        'status' => ExportFile::STATUS_EXPIRED,
                        'file_deleted_at' => now(),
                    ])->save();

                    $deleted++;
                }
            }
        });

    $this->info('Export cleanup');
    $this->table(['Metric', 'Value'], [
        ['Expired records matched', number_format($matched)],
        ['Files deleted/records expired', number_format($deleted)],
        ['Mode', $delete ? 'delete' : 'dry-run'],
        ['Grace hours', (string) $graceHours],
    ]);

    if (! $delete && $matched > 0) {
        $this->line('Run with --delete to remove files and mark records expired.');
    }

    return 0;
})->purpose('Prune expired generated export files while keeping audit records');

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

Artisan::command('dashboard:warm-cache', function () {
    $admin = User::role('administrator')->where('is_active', true)->first();

    if (! $admin) {
        $this->warn('No active administrator found for cache warm-up.');

        return 0;
    }

    $startedAt = microtime(true);

    // Build dashboard payload for admin unfiltered — this populates the cache
    $controller = new DashboardController;
    $user = $admin;
    $filters = ['cityId' => null, 'districtId' => null, 'villageId' => null, 'gender' => null, 'category' => null];

    DashboardCache::warmUp($user, $filters, function () use ($user): array {
        $baseQuery = SurveyResponse::query();
        SurveyResponseAccess::applyVisibleScope($baseQuery, $user);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'verified' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_VERIFIED)->count(),
            'need_revision' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_NEED_REVISION)->count(),
            'submitted' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_SUBMITTED)->count(),
            'draft' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_DRAFT)->count(),
            'rejected' => (clone $baseQuery)->where('status', SurveyResponse::STATUS_REJECTED)->count(),
        ];

        $factReader = app(DashboardFactReader::class);
        if ($factReader->hasFacts()) {
            return $factReader->build($user, null, null, null, null, null, $stats);
        }

        return ['stats' => $stats, 'questionAnalytics' => [], 'dashboardSummary' => ['response_count' => $stats['total'], 'questions_total' => 0, 'questions_with_data' => 0, 'completion_pct' => 0], 'categoryOptions' => collect(), 'category' => null];
    });

    $duration = round(microtime(true) - $startedAt, 2);
    $this->info("Dashboard cache warmed for administrator (unfiltered) in {$duration}s.");

    return 0;
})->purpose('Pre-warm the dashboard analytics cache for the unfiltered administrator view');
