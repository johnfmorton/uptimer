<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Check Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for a monitored URL to respond.
    | If a URL doesn't respond within this time, it will be marked as down.
    |
    */

    'check_timeout' => (int) env('CHECK_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Check History Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to retain check history data in the database.
    | Checks older than this will be automatically deleted by the scheduled
    | cleanup command. Set to 0 to keep all history indefinitely.
    |
    | Default: 30 days (1 month)
    |
    */

    'check_retention_days' => (int) env('CHECK_RETENTION_DAYS', 30),

];
