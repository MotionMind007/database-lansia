<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id', 'respondent_id', 'questionnaire_number',
        'surveyor_id', 'region_id', 'interview_date',
        'status', 'surveyor_notes', 'submitted_at',
        'verified_at',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'submitted_at'   => 'datetime',
        'verified_at'    => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT         = 'draft';
    const STATUS_SUBMITTED     = 'submitted';
    const STATUS_NEED_REVISION = 'need_revision';
    const STATUS_VERIFIED      = 'verified';
    const STATUS_REJECTED      = 'rejected';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT         => 'Draft',
            self::STATUS_SUBMITTED     => 'Submitted',
            self::STATUS_NEED_REVISION => 'Perlu Revisi',
            self::STATUS_VERIFIED      => 'Verified',
            self::STATUS_REJECTED      => 'Ditolak',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_DRAFT         => 'gray',
            self::STATUS_SUBMITTED     => 'yellow',
            self::STATUS_NEED_REVISION => 'orange',
            self::STATUS_VERIFIED      => 'green',
            self::STATUS_REJECTED      => 'red',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class)->orderByDesc('verified_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RespondentDocument::class);
    }

    // Scope filters
    public function scopeForSurveyor($query, $userId)
    {
        return $query->where('surveyor_id', $userId);
    }

    public function scopePendingVerification($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_NEED_REVISION]);
    }
}
