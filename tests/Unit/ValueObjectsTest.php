<?php

declare(strict_types=1);

use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\SafeUrl;

describe('SafeUrl', function (): void {
    it('accepts valid URLs', function (): void {
        $url = new SafeUrl('https://example.com');

        expect($url->__toString())->toBe('https://example.com');
        expect($url->getHost())->toBe('example.com');
    });

    it('throws on invalid URL', function (): void {
        new SafeUrl('not-a-url');
    })->throws(InvalidArgumentException::class);

    it('trims whitespace', function (): void {
        $url = new SafeUrl('  https://example.com  ');

        expect($url->__toString())->toBe('https://example.com');
    });
});

describe('AuditScore', function (): void {
    it('converts to percentage', function (): void {
        expect((new AuditScore(0.85))->toPercentage())->toBe(85);
        expect((new AuditScore(1.0))->toPercentage())->toBe(100);
        expect((new AuditScore(0.0))->toPercentage())->toBe(0);
    });

    it('returns correct color for thresholds', function (): void {
        expect((new AuditScore(0.95))->getColor())->toBe('green');
        expect((new AuditScore(0.90))->getColor())->toBe('green');
        expect((new AuditScore(0.89))->getColor())->toBe('orange');
        expect((new AuditScore(0.50))->getColor())->toBe('orange');
        expect((new AuditScore(0.49))->getColor())->toBe('red');
    });

    it('determines passing status', function (): void {
        expect((new AuditScore(0.90))->isPassing())->toBeTrue();
        expect((new AuditScore(0.89))->isPassing())->toBeFalse();
    });

    it('throws on out of range values', function (): void {
        new AuditScore(1.5);
    })->throws(InvalidArgumentException::class);
});

describe('MetricValue', function (): void {
    it('parses seconds display value', function (): void {
        $metric = MetricValue::fromDisplayValue('2.1 s');

        expect($metric->toMilliseconds())->toBe(2100.0);
        expect($metric->toSeconds())->toBe(2.1);
    });

    it('parses milliseconds display value', function (): void {
        $metric = MetricValue::fromDisplayValue('500 ms');

        expect($metric->toMilliseconds())->toBe(500.0);
    });

    it('parses unitless value (CLS)', function (): void {
        $metric = MetricValue::fromDisplayValue('0.001');

        expect($metric->getValue())->toBe(0.001);
        expect($metric->format())->toBe('0.001');
    });

    it('formats values appropriately', function (): void {
        expect(MetricValue::fromDisplayValue('0.6 s')->format())->toBe('0.6 s');
        expect(MetricValue::fromDisplayValue('0.5 s')->format())->toBe('0.5 s');
        expect(MetricValue::fromDisplayValue('2100 ms')->format())->toBe('2.1 s');
        expect(MetricValue::fromDisplayValue('50 ms')->format())->toBe('50 ms');
    });
});
