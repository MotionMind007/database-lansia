<?php

namespace App\Support;

use App\Models\DashboardAnswerFact;
use App\Models\Region;
use App\Models\SurveyResponse;
use App\Models\User;

class DashboardBenchmark
{
    public function run(User $user, int $iterations = 3, bool $includeRebuild = false, int $chunk = 500): array
    {
        $iterations = max(1, $iterations);

        $statsTimes = [];
        $analyticsTimes = [];
        $healthTimes = [];
        $rebuildSeconds = null;

        if ($includeRebuild) {
            $startedAt = microtime(true);
            app(DashboardFactBuilder::class)->rebuildAll($chunk);
            $rebuildSeconds = $this->secondsSince($startedAt);
        }

        for ($i = 0; $i < $iterations; $i++) {
            $startedAt = microtime(true);
            $stats = $this->stats($user);
            $statsTimes[] = $this->secondsSince($startedAt);

            $startedAt = microtime(true);
            app(DashboardFactReader::class)->build($user, null, null, null, null, null, $stats);
            $analyticsTimes[] = $this->secondsSince($startedAt);

            $startedAt = microtime(true);
            app(DashboardHealthCheck::class)->run();
            $healthTimes[] = $this->secondsSince($startedAt);
        }

        return [
            'dataset' => [
                'survey_responses' => SurveyResponse::query()->count(),
                'dashboard_fact_rows' => DashboardAnswerFact::query()->count(),
                'dashboard_fact_responses' => DashboardAnswerFact::query()->distinct()->count('survey_response_id'),
                'cities' => Region::query()->where('type', 'city')->count(),
                'districts' => Region::query()->where('type', 'district')->count(),
                'villages' => Region::query()->where('type', 'village')->count(),
            ],
            'timings' => [
                'stats_seconds' => $this->summarize($statsTimes),
                'analytics_seconds' => $this->summarize($analyticsTimes),
                'health_seconds' => $this->summarize($healthTimes),
                'rebuild_seconds' => $rebuildSeconds,
            ],
            'iterations' => $iterations,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => SurveyResponseAccess::roleFor($user),
            ],
        ];
    }

    private function stats(User $user): array
    {
        $query = SurveyResponse::query();
        SurveyResponseAccess::applyVisibleScope($query, $user);

        return [
            'total' => (clone $query)->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'verified' => (clone $query)->where('status', SurveyResponse::STATUS_VERIFIED)->count(),
            'need_revision' => (clone $query)->where('status', SurveyResponse::STATUS_NEED_REVISION)->count(),
            'submitted' => (clone $query)->where('status', SurveyResponse::STATUS_SUBMITTED)->count(),
            'draft' => (clone $query)->where('status', SurveyResponse::STATUS_DRAFT)->count(),
            'rejected' => (clone $query)->where('status', SurveyResponse::STATUS_REJECTED)->count(),
        ];
    }

    private function summarize(array $times): array
    {
        sort($times);

        return [
            'min' => round($times[0], 4),
            'avg' => round(array_sum($times) / count($times), 4),
            'max' => round($times[count($times) - 1], 4),
        ];
    }

    private function secondsSince(float $startedAt): float
    {
        return microtime(true) - $startedAt;
    }
}
