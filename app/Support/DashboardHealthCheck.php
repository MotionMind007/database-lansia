<?php

namespace App\Support;

use App\Models\DashboardAnswerFact;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardHealthCheck
{
    public function run(): array
    {
        $surveyTotal = SurveyResponse::query()->count();
        $factTableExists = Schema::hasTable('dashboard_answer_facts');
        $factResponseTotal = $factTableExists
            ? DashboardAnswerFact::query()->distinct()->count('survey_response_id')
            : 0;
        $factRows = $factTableExists ? DashboardAnswerFact::query()->count() : 0;
        $missingFacts = max(0, $surveyTotal - $factResponseTotal);
        $pendingJobs = $this->pendingJobs();
        $failedJobs = $this->failedJobs();
        $lastFactUpdate = $factTableExists ? DashboardAnswerFact::query()->max('updated_at') : null;

        $checks = [
            $this->check(
                'dashboard_answer_facts table',
                $factTableExists,
                $factTableExists ? 'available' : 'missing'
            ),
            $this->check(
                'survey responses with dashboard facts',
                $missingFacts === 0,
                "{$factResponseTotal}/{$surveyTotal} responses covered",
                $missingFacts > 0 ? "{$missingFacts} responses missing facts" : null
            ),
            $this->check(
                'pending analytics/default jobs',
                $pendingJobs['total'] <= (int) config('dashboard.health.max_pending_jobs', 1000),
                "{$pendingJobs['total']} pending ({$pendingJobs['analytics']} analytics, {$pendingJobs['default']} default)",
                'queue backlog is above threshold'
            ),
            $this->check(
                'failed jobs',
                $failedJobs <= (int) config('dashboard.health.max_failed_jobs', 0),
                "{$failedJobs} failed jobs",
                'failed jobs need review'
            ),
            $this->check(
                'dashboard fact freshness',
                $this->factsAreFresh($lastFactUpdate, $surveyTotal),
                $lastFactUpdate ? 'last update '.$lastFactUpdate : 'no fact update yet',
                'dashboard facts are stale'
            ),
        ];

        $status = collect($checks)->contains(fn (array $check): bool => $check['status'] === 'fail')
            ? 'fail'
            : 'ok';

        return [
            'status' => $status,
            'summary' => [
                'survey_total' => $surveyTotal,
                'fact_response_total' => $factResponseTotal,
                'fact_rows' => $factRows,
                'pending_jobs' => $pendingJobs['total'],
                'failed_jobs' => $failedJobs,
                'queue_connection' => config('queue.default'),
                'cache_store' => config('cache.default'),
            ],
            'checks' => $checks,
        ];
    }

    private function pendingJobs(): array
    {
        if (! Schema::hasTable('jobs')) {
            return ['total' => 0, 'analytics' => 0, 'default' => 0];
        }

        return [
            'total' => DB::table('jobs')->count(),
            'analytics' => DB::table('jobs')->where('queue', 'analytics')->count(),
            'default' => DB::table('jobs')->where('queue', 'default')->count(),
        ];
    }

    private function failedJobs(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return DB::table('failed_jobs')->count();
    }

    private function factsAreFresh(?string $lastFactUpdate, int $surveyTotal): bool
    {
        if ($surveyTotal === 0) {
            return true;
        }

        if (! $lastFactUpdate) {
            return false;
        }

        return now()->diffInMinutes($lastFactUpdate) <= (int) config('dashboard.health.fact_stale_minutes', 1440);
    }

    private function check(string $name, bool $passes, string $detail, ?string $failure = null): array
    {
        return [
            'name' => $name,
            'status' => $passes ? 'ok' : 'fail',
            'detail' => $passes ? $detail : ($failure ?? $detail),
        ];
    }
}
