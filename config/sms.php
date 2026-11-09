<?php

return [
    'enabled' => env('SMS_ENABLED', false),
    'driver' => env('SMS_DRIVER', 'log'),
    'drivers' => [
        'gateway' => [
            'base_url' => env('SMS_BASE_URL'),
            'api_key' => env('SMS_API_KEY'),
            'sender' => env('SMS_SENDER'),
        ],
    ],
];
