<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestionOption extends Model
{
    protected $fillable = [
        'question_id', 'option_label', 'option_value', 'score', 'sort_order',
    ];

    protected $casts = ['score' => 'decimal:2'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }
}
