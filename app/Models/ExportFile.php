<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'disk',
    'path',
    'row_count',
    'status',
    'expires_at',
    'last_downloaded_at',
    'file_deleted_at',
])]
class ExportFile extends Model
{
    public const STATUS_READY = 'ready';

    public const STATUS_EXPIRED = 'expired';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'file_deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
