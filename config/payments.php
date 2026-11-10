<?php

$fields = json_decode((string) env('PAYMENTS_FIELD_MAP', ''), true);

return [
    'enabled' => env('PAYMENTS_ENABLED', false),
    'driver' => env('PAYMENTS_DRIVER', 'mock'),
    'mock_explicit' => env('PAYMENTS_DRIVER') === 'mock',
    'expiry_minutes' => (int) env('PAYMENTS_EXPIRY_MINUTES', 30),

    'drivers' => [
        'gateway' => [
            'base_url' => env('PAYMENTS_BASE_URL'),
            'merchant_id' => env('PAYMENTS_MERCHANT_ID'),
            'api_key' => env('PAYMENTS_API_KEY'),
            'sandbox' => env('PAYMENTS_SANDBOX', true),
            'callback_url' => env('PAYMENTS_CALLBACK_URL'),
            'initiate_path' => env('PAYMENTS_INITIATE_PATH', '/payments'),
            'verify_path' => env('PAYMENTS_VERIFY_PATH', '/payments/verify'),
            'checkout_path' => env('PAYMENTS_CHECKOUT_PATH', '/checkout/{authority}'),
            'success_status' => env('PAYMENTS_SUCCESS_STATUS', 'success'),
            'cancel_status' => env('PAYMENTS_CANCEL_STATUS', 'cancelled'),
            // Map flat request keys and dotted response paths to the selected API's fields.
            'fields' => array_replace([
                'merchant_id' => 'merchant_id',
                'amount' => 'amount',
                'callback_url' => 'callback_url',
                'description' => 'description',
                'mobile' => 'mobile',
                'sandbox' => 'sandbox',
                'initiate_authority' => 'authority',
                'callback_authority' => 'authority',
                'verify_authority' => 'authority',
                'verified_status' => 'status',
                'verified_amount' => 'amount',
                'verified_reference' => 'reference_id',
            ], is_array($fields) ? array_filter($fields, 'is_string') : []),
        ],
    ],
];
