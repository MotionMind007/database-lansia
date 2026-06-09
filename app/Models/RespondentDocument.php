<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RespondentDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'respondent_id', 'survey_response_id', 'document_type',
        'file_path', 'file_name', 'mime_type', 'file_size',
        'notes', 'is_latest', 'uploaded_by',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
    ];

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return config('uploads.documents.types')[$this->document_type] ?? 'Dokumen Lainnya';
    }
}
