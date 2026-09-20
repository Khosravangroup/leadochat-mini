<?php

return [
    'headers_enabled' => env('SECURITY_HEADERS_ENABLED', true),

    'csp_report_only' => [
        'enabled' => env('CSP_REPORT_ONLY_ENABLED', true),
        'report_path' => '/csp-reports',
        'max_report_bytes' => (int) env('CSP_REPORT_MAX_BYTES', 65536),
        'max_batch_size' => (int) env('CSP_REPORT_MAX_BATCH', 20),
        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'img-src' => ["'self'", 'data:', 'blob:', 'https:'],
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            'connect-src' => ["'self'", 'wss:', 'ws:'],
            'media-src' => ["'self'", 'blob:', 'https:'],
            'frame-src' => ["'self'"],
            'worker-src' => ["'self'", 'blob:'],
            'manifest-src' => ["'self'"],
            'upgrade-insecure-requests' => [],
        ],
    ],

    'hsts' => [
        'enabled' => env('SECURITY_HSTS_ENABLED', true),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 86400),
    ],
];
