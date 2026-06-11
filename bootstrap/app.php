<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('dashboard:health --fail-on-warning')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/dashboard-health.log'));

        $schedule->command('dashboard:warm-cache')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        if (config('dashboard.scheduled_rebuild.enabled')) {
            $schedule->command('dashboard:rebuild-facts --chunk='.config('dashboard.scheduled_rebuild.chunk'))
                ->dailyAt(config('dashboard.scheduled_rebuild.time'))
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/dashboard-rebuild.log'));
        }

        $schedule->command('exports:prune --delete --grace-hours='.config('exports.cleanup_grace_hours'))
            ->dailyAt(config('exports.cleanup_schedule_time'))
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/export-cleanup.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
