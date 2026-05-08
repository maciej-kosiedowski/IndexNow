<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IndexNow Schedule Cron
    |--------------------------------------------------------------------------
    |
    | Define the cron expression for how often the IndexNowSubmitJob should run.
    | Default is every hour.
    |
    */
    'schedule' => env('INDEXNOW_SCHEDULE_CRON', '0 * * * *'),

    /*
    |--------------------------------------------------------------------------
    | IndexNow API Keys
    |--------------------------------------------------------------------------
    |
    | Map of search engines to your specific API keys.
    | For example: 'bing' => env('INDEXNOW_BING_KEY')
    |
    */
    'keys' => [
        'bing' => env('INDEXNOW_BING_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow Cache Key
    |--------------------------------------------------------------------------
    |
    | The key used to store URLs in the cache before they are submitted.
    |
    */
    'cache_key' => 'indexnow_pending_urls',
];
