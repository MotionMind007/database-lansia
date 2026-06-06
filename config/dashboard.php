<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Cache TTL
    |--------------------------------------------------------------------------
    |
    | Cached analytic payloads prevent repeated JSON aggregation on every page
    | load. Data writes bump the cache version, so stale entries are naturally
    | abandoned even when the cache driver does not support tags.
    |
    */

    'cache_ttl' => env('DASHBOARD_CACHE_TTL', 900),

    'raw_fallback_limit' => env('DASHBOARD_RAW_FALLBACK_LIMIT', 5000),

    'health' => [
        'max_pending_jobs' => env('DASHBOARD_HEALTH_MAX_PENDING_JOBS', 1000),
        'max_failed_jobs' => env('DASHBOARD_HEALTH_MAX_FAILED_JOBS', 0),
        'fact_stale_minutes' => env('DASHBOARD_HEALTH_FACT_STALE_MINUTES', 1440),
    ],

    'scheduled_rebuild' => [
        'enabled' => env('DASHBOARD_SCHEDULED_REBUILD_ENABLED', false),
        'time' => env('DASHBOARD_SCHEDULED_REBUILD_TIME', '02:00'),
        'chunk' => env('DASHBOARD_SCHEDULED_REBUILD_CHUNK', 500),
    ],
];
