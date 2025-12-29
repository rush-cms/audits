<?php

declare(strict_types=1);

return [
    'brand_name' => env('AUDITS_BRAND_NAME', 'Rush CMS'),
    'logo_path' => env('AUDITS_LOGO_PATH'),

    'report' => [
        'date_format' => env('AUDITS_DATE_FORMAT', 'd/m/Y H:i'),
        'show_seo' => env('AUDITS_SHOW_SEO', false),
        'show_accessibility' => env('AUDITS_SHOW_ACCESSIBILITY', false),
        'cta_url' => env('AUDITS_CTA_URL', 'https://wa.me/5511999999999'),
    ],

    'pagespeed' => [
        'api_key' => env('PAGESPEED_API_KEY'),
    ],

    'webhook' => [
        'return_url' => env('AUDITS_WEBHOOK_RETURN_URL'),
        'timeout' => (int) env('AUDITS_WEBHOOK_TIMEOUT', 30),
    ],

    'pdf' => [
        'retention_days' => (int) env('AUDITS_RETENTION_DAYS', 7),
    ],

    'idempotency_window' => (int) env('AUDITS_IDEMPOTENCY_WINDOW', 60),

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/usr/bin/node'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY', '/usr/bin/npm'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/google-chrome'),
    ],
];
