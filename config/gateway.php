<?php

declare(strict_types=1);

return [
    // Secret shared with downstream services: proves a request came through the gateway.
    'internal_api_key' => env('INTERNAL_API_KEY', ''),

    'proxy' => [
        'timeout' => (int)env('PROXY_TIMEOUT', 15),
        // Only these caller headers are passed downstream.
        'forwarded_headers' => ['Accept', 'Accept-Language', 'App-Version', 'Idempotency-Key', 'X-Signature'],
    ],

    'otp' => [
        'length'                  => (int)env('OTP_LENGTH', 6),
        'ttl_minutes'             => (int)env('OTP_TTL_MINUTES', 5),
        'max_attempts'            => (int)env('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int)env('OTP_RESEND_COOLDOWN', 60),
        'max_resends'             => (int)env('OTP_MAX_RESENDS', 3),
    ],

    'notifications' => [
        // Empty URL → codes are only written to the log (never revealed outside local/testing).
        'url'     => env('NOTIFICATION_SERVICE_URL', ''),
        'api_key' => env('NOTIFICATION_SERVICE_API_KEY', ''),
    ],

    'seed' => [
        'admin_password' => env('SEED_ADMIN_PASSWORD', 'admin-password-123'),
        'client_secret'  => env('SEED_CLIENT_SECRET', 'local-web-client-secret'),
        'services'       => [
            'orders' => env('ORDER_SERVICE_URL', 'http://order-nginx'),
        ],
    ],
];
