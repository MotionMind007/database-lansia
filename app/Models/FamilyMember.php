<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'respondent_id', 'name', 'gender', 'age',
        'status', 'education', 'occupation', 'ktp_status',
    ];

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }
}
