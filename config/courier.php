<?php
return [
    'default' => env('COURIER_DRIVER', 'steadfast'),

    'rate_limit' => env('COURIER_RATE_LIMIT', 50),
    'rate_decay' => env('COURIER_RATE_DECAY', 60), // in seconds

    'steadfast' => [
        'api_key' => env('STEADFAST_API_KEY'),
        'secret_key' => env('STEADFAST_SECRET_KEY'),
        'base_url' => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),
    ],
];
