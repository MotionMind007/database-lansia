<?php

return [
    'download_ttl_hours' => max(1, (int) env('EXPORT_DOWNLOAD_TTL_HOURS', 24)),
    'cleanup_schedule_time' => env('EXPORT_CLEANUP_SCHEDULE_TIME', '03:00'),
    'cleanup_grace_hours' => max(0, (int) env('EXPORT_CLEANUP_GRACE_HOURS', 0)),
];
