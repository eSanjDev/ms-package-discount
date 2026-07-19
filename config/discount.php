<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discount Service Base URL
    |--------------------------------------------------------------------------
    | The base URL of the Esanj Discount microservice (coupons & gift cards),
    | without a trailing slash.
    */
    'base_url' => env('DISCOUNT_SERVICE_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Credentials
    |--------------------------------------------------------------------------
    | The service credentials used to obtain an access token. The token itself
    | is issued by the OAuth server configured for the auth-bridge package
    | (esanj.auth_bridge.base_url) via the client_credentials grant, and is then
    | sent as a Bearer token to the Discount service.
    */
    'client_id' => env('DISCOUNT_CLIENT_ID'),
    'client_secret' => env('DISCOUNT_CLIENT_SECRET'),
    'scope' => env('DISCOUNT_SCOPE', '*'),

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    | attempts : total number of attempts (1 = no retry).
    | sleep_ms : milliseconds to wait between retries.
    */
    'retry' => [
        'attempts' => (int) env('DISCOUNT_RETRY_ATTEMPTS', 3),
        'sleep_ms' => (int) env('DISCOUNT_RETRY_SLEEP_MS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('DISCOUNT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | The log channel used by the client. null = the application default channel.
    */
    'logging' => [
        'channel' => env('DISCOUNT_LOG_CHANNEL'),
    ],
];