<?php

declare(strict_types=1);

use App\Data\AuditData;

it('parses lighthouseResult object extracting only vital metrics', function (): void {
    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $json = file_get_contents($fixturePath);
    $payload = json_decode($json, true);

    $lighthouseResult = $payload['lighthouseResult'];

    $auditData = AuditData::fromLighthouseResult($lighthouseResult);

    expect($auditData->targetUrl->__toString())->toBe('https://padariaesquinadopao.com.br/');
    expect($auditData->score->toPercentage())->toBe(69);
    expect($auditData->score->getColor())->toBe('orange');
    expect($auditData->score->isPassing())->toBeFalse();
    expect($auditData->auditId)->toBeString()->not->toBeEmpty();

    expect($auditData->seo)->not->toBeNull();
    expect($auditData->seo->score->toPercentage())->toBe(92);

    expect($auditData->accessibility)->not->toBeNull();
    expect($auditData->accessibility->score->toPercentage())->toBe(96);
});

it('handles direct lighthouseResult without wrapper', function (): void {
    $fixturePath = dirname(__DIR__).'/Fixtures/pagespeed_mock.json';
    $json = file_get_contents($fixturePath);
    $payload = json_decode($json, true);

    $lighthouseResult = $payload['lighthouseResult'];

    $auditData = AuditData::fromLighthouseResult($lighthouseResult);

    expect($auditData->targetUrl->__toString())->toBe('https://padariaesquinadopao.com.br/');
    expect($auditData->score->toPercentage())->toBe(69);
});
