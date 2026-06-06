<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    private const VERSION_KEY = 'dashboard.analytics.version';

    public static function remember(User $user, array $filters, Closure $callback): array
    {
        $ttl = max(0, (int) config('dashboard.cache_ttl', 900));

        if ($ttl === 0) {
            return $callback();
        }

        return Cache::remember(
            self::key($user, $filters),
            now()->addSeconds($ttl),
            $callback
        );
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function key(User $user, array $filters): string
    {
        $payload = [
            'version' => self::version(),
            'role' => SurveyResponseAccess::roleFor($user),
            'user_id' => $user->id,
            'filters' => $filters,
        ];

        return 'dashboard.analytics.'.sha1(json_encode($payload));
    }

    private static function version(): int
    {
        $version = Cache::get(self::VERSION_KEY);

        if (! is_numeric($version)) {
            $version = 1;
            Cache::forever(self::VERSION_KEY, $version);
        }

        return (int) $version;
    }
}
