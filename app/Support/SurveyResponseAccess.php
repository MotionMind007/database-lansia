<?php

namespace App\Support;

use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SurveyResponseAccess
{
    public static function roleFor(User $user): ?string
    {
        if ($user->hasAnyRole(['administrator', 'super admin', 'super_admin'])) {
            return 'administrator';
        }

        if ($user->hasRole('surveyor')) {
            return 'surveyor';
        }

        if ($user->hasRole('verifikator')) {
            return 'verifikator';
        }

        return null;
    }

    public static function applyVisibleScope(Builder $query, User $user): Builder
    {
        $role = self::roleFor($user);

        if ($role === 'administrator') {
            return $query;
        }

        if ($role === 'surveyor') {
            return $query->where('surveyor_id', $user->id);
        }

        if ($role === 'verifikator') {
            return $query->whereIn('status', self::verifiableStatuses());
        }

        abort(403, 'Anda tidak memiliki akses ke data ini.');
    }

    public static function applyVerificationScope(Builder $query): Builder
    {
        return $query->whereIn('status', self::verifiableStatuses());
    }

    public static function canView(User $user, SurveyResponse $response): bool
    {
        $role = self::roleFor($user);

        return match ($role) {
            'administrator' => true,
            'surveyor' => (int) $response->surveyor_id === (int) $user->id,
            'verifikator' => in_array($response->status, self::verifiableStatuses(), true),
            default => false,
        };
    }

    public static function verifiableStatuses(): array
    {
        return [
            SurveyResponse::STATUS_SUBMITTED,
            SurveyResponse::STATUS_NEED_REVISION,
        ];
    }
}
