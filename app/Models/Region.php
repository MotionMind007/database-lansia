<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to filter by type level
     */
    public function scopeProvince($query)
    {
        return $query->where('type', 'province');
    }

    public function scopeCity($query)
    {
        return $query->where('type', 'city');
    }

    public function scopeDistrict($query)
    {
        return $query->where('type', 'district');
    }

    public function scopeVillage($query)
    {
        return $query->where('type', 'village');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
