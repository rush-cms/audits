<?php

declare(strict_types=1);

use App\Data\AuditData;

it('parses lighthouseResult object extracting only vital metrics', function (): void {
    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $json = file_get_contents($fixturePath);
    $payload = json_decode($json, true);

    $data = $payload[0] ?? $payload;
    $lighthouseResult = $data['lighthouseResult'];

    $auditData = AuditData::fromLighthouseResult($lighthouseResult);

    expect($auditData->targetUrl->__toString())->toBe('https://www.rafhael.com.br/');
    expect($auditData->score->toPercentage())->toBe(100);
    expect($auditData->score->getColor())->toBe('green');
    expect($auditData->score->isPassing())->toBeTrue();
    expect($auditData->lcp->format())->toBe('0.6 s');
    expect($auditData->fcp->format())->toBe('0.5 s');
    expect($auditData->cls->format())->toBe('0.001');
    expect($auditData->auditId)->toBeString()->not->toBeEmpty();
});

it('handles direct lighthouseResult without wrapper', function (): void {
    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $json = file_get_contents($fixturePath);
    $payload = json_decode($json, true);

    $data = $payload[0] ?? $payload;
    $lighthouseResult = $data['lighthouseResult'];

    $auditData = AuditData::fromLighthouseResult($lighthouseResult);

    expect($auditData->targetUrl->__toString())->toBe('https://www.rafhael.com.br/');
    expect($auditData->score->toPercentage())->toBe(100);
});
