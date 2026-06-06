<?php

namespace App\Jobs;

use App\Models\DashboardAnswerFact;
use App\Models\SurveyResponse;
use App\Support\DashboardCache;
use App\Support\DashboardFactBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncDashboardFacts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly int $surveyResponseId,
        private readonly string $operation = 'rebuild',
    ) {
        $this->onQueue('analytics');
    }

    public static function rebuild(int $surveyResponseId): self
    {
        return new self($surveyResponseId, 'rebuild');
    }

    public static function syncStatus(int $surveyResponseId): self
    {
        return new self($surveyResponseId, 'status');
    }

    public function handle(DashboardFactBuilder $builder): void
    {
        $response = SurveyResponse::with(['respondent', 'region.parent.parent', 'answers'])
            ->find($this->surveyResponseId);

        if (! $response) {
            DashboardAnswerFact::where('survey_response_id', $this->surveyResponseId)->delete();
            DashboardCache::flush();

            return;
        }

        if ($this->operation === 'status') {
            $builder->syncResponseStatus($response);

            return;
        }

        $builder->rebuildForResponse($response);
    }
}
