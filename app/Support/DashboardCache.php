<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardCache
{
    private const VERSION_KEY = 'dashboard.analytics.version';

    /**
     * Remember dashboard payload with graceful degradation.
     * If cache is available, use it. If computation fails, return stale cache or empty state.
     */
    public static function remember(User $user, array $filters, Closure $callback): array
    {
        $ttl = max(0, (int) config('dashboard.cache_ttl', 900));

        if ($ttl === 0) {
            return self::safeCompute($callback);
        }

        $key = self::key($user, $filters);

        // Try to get from cache first
        try {
            $cached = Cache::get($key);

            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache read failed, computing fresh.', ['error' => $e->getMessage()]);
        }

        // Compute fresh data
        $result = self::safeCompute($callback);

        // Store in cache (best-effort)
        try {
            Cache::put($key, $result, now()->addSeconds($ttl));
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache write failed.', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Pre-warm dashboard cache for a given user/filter combination.
     */
    public static function warmUp(User $user, array $filters, Closure $callback): void
    {
        $ttl = max(0, (int) config('dashboard.cache_ttl', 900));

        if ($ttl === 0) {
            return;
        }

        $key = self::key($user, $filters);
        $result = self::safeCompute($callback);

        try {
            Cache::put($key, $result, now()->addSeconds($ttl));
        } catch (\Throwable $e) {
            Log::warning('Dashboard warm-up cache write failed.', ['error' => $e->getMessage()]);
        }
    }

    public static function flush(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, self::version() + 1);
        } catch (\Throwable $e) {
            Log::warning('Dashboard cache flush failed.', ['error' => $e->getMessage()]);
        }
    }

    private static function safeCompute(Closure $callback): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error('Dashboard computation failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::emptyPayload();
        }
    }

    private static function emptyPayload(): array
    {
        return [
            'stats' => [
                'total' => 0,
                'this_month' => 0,
                'verified' => 0,
                'need_revision' => 0,
                'submitted' => 0,
                'draft' => 0,
                'rejected' => 0,
            ],
            'questionAnalytics' => [],
            'dashboardSummary' => [
                'response_count' => 0,
                'questions_total' => 0,
                'questions_with_data' => 0,
                'completion_pct' => 0,
            ],
            'categoryOptions' => collect(),
            'category' => null,
        ];
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
        try {
            $version = Cache::get(self::VERSION_KEY);

            if (! is_numeric($version)) {
                $version = 1;
                Cache::forever(self::VERSION_KEY, $version);
            }

            return (int) $version;
        } catch (\Throwable) {
            return 1;
        }
    }
}
