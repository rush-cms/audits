<?php

declare(strict_types=1);

/**
 * Blocked Domains Configuration
 *
 * Add domains you want to block from being audited.
 * Subdomains are also matched (e.g., 'example.com' blocks 'www.example.com').
 *
 * Note: This only applies in production (APP_ENV=production).
 * In local/development environments, SSRF protection is disabled.
 */
return [
    // 'internal.company.com',
    // 'staging.myapp.com',
    // 'localhost.example.com',
];
