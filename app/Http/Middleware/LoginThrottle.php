<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brute-force lockout: blocks login attempts after too many failures.
 *
 * - Max 10 attempts per email/IP combo within 30 minutes
 * - Lockout lasts 60 minutes after threshold hit
 * - Independent from Laravel's built-in throttle middleware
 */
class LoginThrottle
{
    private const MAX_ATTEMPTS = 10;

    private const LOCKOUT_MINUTES = 60;

    private const DECAY_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST')) {
            $key = $this->lockoutKey($request);

            if ($this->isLockedOut($key)) {
                $minutes = $this->remainingLockoutMinutes($key);

                try {
                    activity('auth')
                        ->event('login_lockout_blocked')
                        ->withProperties([
                            'login' => $request->input('email'),
                            'ip' => $request->ip(),
                            'remaining_minutes' => $minutes,
                        ])
                        ->log('Login diblokir karena lockout aktif.');
                } catch (\Throwable) {
                    // Activity log failure should not prevent lockout response
                }

                return back()->withErrors([
                    'email' => "Akun terkunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$minutes} menit.",
                ])->withInput($request->only('email'));
            }
        }

        return $next($request);
    }

    /**
     * Record a failed login attempt. Call this from LoginController.
     */
    public static function recordFailedAttempt(Request $request): void
    {
        $key = self::attemptKey($request);

        RateLimiter::hit($key, self::DECAY_MINUTES * 60);

        if (RateLimiter::attempts($key) >= self::MAX_ATTEMPTS) {
            self::lockout($request);
        }
    }

    /**
     * Clear attempts after successful login.
     */
    public static function clearAttempts(Request $request): void
    {
        RateLimiter::clear(self::attemptKey($request));
        Cache::forget(self::lockoutCacheKey($request));
    }

    private function isLockedOut(string $key): bool
    {
        return Cache::has($key);
    }

    private function remainingLockoutMinutes(string $key): int
    {
        $expiresAt = Cache::get($key);

        if (! $expiresAt) {
            return 0;
        }

        return max(1, (int) ceil(($expiresAt - time()) / 60));
    }

    private static function lockout(Request $request): void
    {
        $key = self::lockoutCacheKey($request);
        $expiresAt = time() + (self::LOCKOUT_MINUTES * 60);

        Cache::put($key, $expiresAt, self::LOCKOUT_MINUTES * 60);

        try {
            activity('auth')
                ->event('login_lockout_activated')
                ->withProperties([
                    'login' => $request->input('email'),
                    'ip' => $request->ip(),
                    'lockout_minutes' => self::LOCKOUT_MINUTES,
                ])
                ->log('Lockout diaktifkan setelah '.self::MAX_ATTEMPTS.' percobaan gagal.');
        } catch (\Throwable) {
            // Activity log failure should not prevent lockout from working
        }
    }

    private function lockoutKey(Request $request): string
    {
        return self::lockoutCacheKey($request);
    }

    private static function lockoutCacheKey(Request $request): string
    {
        $identifier = strtolower(trim((string) $request->input('email'))).'|'.$request->ip();

        return 'login_lockout:'.sha1($identifier);
    }

    private static function attemptKey(Request $request): string
    {
        $identifier = strtolower(trim((string) $request->input('email'))).'|'.$request->ip();

        return 'login_attempts:'.sha1($identifier);
    }
}
