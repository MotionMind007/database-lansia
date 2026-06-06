<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAnswerFact extends Model
{
    protected $fillable = [
        'survey_response_id',
        'respondent_id',
        'survey_id',
        'surveyor_id',
        'city_id',
        'district_id',
        'region_id',
        'status',
        'gender',
        'question_key',
        'question_number',
        'question_label',
        'question_group',
        'question_kind',
        'question_display',
        'question_sort',
        'row_label',
        'column_label',
        'response_created_at',
    ];

    protected $casts = [
        'response_created_at' => 'datetime',
    ];

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }
}
