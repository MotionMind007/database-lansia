<?php

namespace Tests\Unit;

use App\Http\Middleware\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear($this->attemptKey());
    }

    public function test_allows_request_when_not_locked_out(): void
    {
        $request = $this->makeLoginRequest();
        $middleware = new LoginThrottle();

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_records_failed_attempt(): void
    {
        $request = $this->makeLoginRequest();

        LoginThrottle::recordFailedAttempt($request);

        $this->assertSame(1, RateLimiter::attempts($this->attemptKey()));
    }

    public function test_locks_out_after_10_failed_attempts(): void
    {
        $request = $this->makeLoginRequest();

        for ($i = 0; $i < 10; $i++) {
            LoginThrottle::recordFailedAttempt($request);
        }

        $middleware = new LoginThrottle();
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_clear_attempts_removes_lockout(): void
    {
        $request = $this->makeLoginRequest();

        for ($i = 0; $i < 10; $i++) {
            LoginThrottle::recordFailedAttempt($request);
        }

        LoginThrottle::clearAttempts($request);

        $middleware = new LoginThrottle();
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }

    private function makeLoginRequest(): Request
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }

    private function attemptKey(): string
    {
        $identifier = 'test@example.com|127.0.0.1';

        return 'login_attempts:'.sha1($identifier);
    }
}
