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
        'secret' => env('AUDITS_WEBHOOK_SECRET'),
    ],

    'pdf' => [
        'retention_days' => (int) env('AUDITS_RETENTION_DAYS', 7),
    ],

    'idempotency_window' => (int) env('AUDITS_IDEMPOTENCY_WINDOW', 60),

    'failed_retry_after' => (int) env('AUDITS_FAILED_RETRY_AFTER', 300),

    'require_screenshots' => (bool) env('AUDITS_REQUIRE_SCREENSHOTS', false),

    'failed_jobs_retention_days' => (int) env('AUDITS_FAILED_JOBS_RETENTION_DAYS', 30),

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/usr/bin/node'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY', '/usr/bin/npm'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/google-chrome'),
    ],

    'security' => [
        'blocked_domains' => require config_path('blocked-domains.php'),
    ],

    'rate_limit' => [
        'per_minute' => (int) env('AUDITS_RATE_LIMIT_PER_MINUTE', 60),
        'per_hour' => (int) env('AUDITS_RATE_LIMIT_PER_HOUR', 500),
        'per_day' => (int) env('AUDITS_RATE_LIMIT_PER_DAY', 2000),
        'global_per_minute' => (int) env('AUDITS_RATE_LIMIT_GLOBAL_PER_MINUTE', 200),
    ],
];
