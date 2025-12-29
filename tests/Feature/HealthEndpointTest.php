<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('returns health status with correct structure', function () {
    $response = get('/health');

    expect($response->status())->toBeIn([200, 503]);
    $response->assertJsonStructure([
        'status',
        'checks' => [
            'database',
            'redis',
            'queue',
            'disk',
            'chromium',
        ],
        'metrics' => [
            'queue_depth',
            'failed_jobs_last_hour',
            'disk_usage_percent',
            'audits_last_hour',
        ],
    ]);
});

it('checks database connection', function () {
    $response = get('/health');

    expect($response->json('checks.database'))->toBeIn(['ok', 'slow', 'fail']);
});

it('checks redis connection', function () {
    $response = get('/health');

    expect($response->json('checks.redis'))->toBeIn(['ok', 'slow', 'fail']);
});

it('checks queue status', function () {
    $response = get('/health');

    expect($response->json('checks.queue'))->toBeIn(['ok', 'warning', 'fail']);
});

it('checks disk status', function () {
    $response = get('/health');

    expect($response->json('checks.disk'))->toBeIn(['ok', 'warning', 'critical', 'fail']);
});

it('checks chromium availability', function () {
    $response = get('/health');

    expect($response->json('checks.chromium'))->toBeIn(['ok', 'fail']);
});

it('returns metrics with valid values', function () {
    $response = get('/health');

    expect($response->json('metrics.queue_depth'))->toBeInt();
    expect($response->json('metrics.failed_jobs_last_hour'))->toBeInt();
    expect($response->json('metrics.disk_usage_percent'))->toBeInt();
    expect($response->json('metrics.audits_last_hour'))->toBeInt();
});

it('completes health check in under 2 seconds', function () {
    $start = microtime(true);
    get('/health');
    $duration = (microtime(true) - $start) * 1000;

    expect($duration)->toBeLessThan(2000);
});
