<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * HTTP health check endpoint for load balancers and uptime monitors.
     *
     * Returns 200 if all services are reachable, 503 if any check fails.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
            $healthy = false;
        }

        // Cache
        try {
            $key = 'health-check-'.bin2hex(random_bytes(4));
            Cache::put($key, true, 10);
            $checks['cache'] = Cache::get($key) === true ? 'ok' : 'fail';
            Cache::forget($key);
        } catch (\Throwable $e) {
            $checks['cache'] = 'fail';
            $healthy = false;
        }

        if ($checks['cache'] === 'fail') {
            $healthy = false;
        }

        // Queue table exists (for database driver)
        try {
            if (config('queue.default') === 'database') {
                DB::table(config('queue.connections.database.table', 'jobs'))->count();
            }
            $checks['queue'] = 'ok';
        } catch (\Throwable $e) {
            $checks['queue'] = 'fail';
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
