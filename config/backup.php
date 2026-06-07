<?php

return [
    'database' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'path' => trim((string) env('BACKUP_DATABASE_PATH', 'backups/database'), '/'),
        'keep_latest' => max(1, (int) env('BACKUP_DATABASE_KEEP_LATEST', 14)),
        'max_age_hours' => max(1, (int) env('BACKUP_DATABASE_MAX_AGE_HOURS', 26)),
        'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
        'pg_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
    ],
];
