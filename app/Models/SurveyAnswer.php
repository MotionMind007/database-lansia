<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    protected $fillable = [
        'survey_response_id', 'question_id', 'option_id',
        'answer_text', 'answer_number', 'answer_json',
    ];

    protected $casts = [
        'answer_json'   => 'array',
        'answer_number' => 'decimal:2',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
