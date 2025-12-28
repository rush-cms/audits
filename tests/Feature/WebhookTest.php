<?php

declare(strict_types=1);

use App\Data\AuditData;
use App\Services\WebhookDispatcherService;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Support\Facades\Http;

it('dispatches webhook with correct payload', function (): void {
    Http::fake();

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);

    $auditData = new AuditData(
        targetUrl: new Url('https://example.com'),
        score: new AuditScore(0.95),
        lcp: MetricValue::fromDisplayValue('0.6 s'),
        fcp: MetricValue::fromDisplayValue('0.5 s'),
        cls: MetricValue::fromDisplayValue('0.001'),
        auditId: 'test-audit-123',
    );

    $service = new WebhookDispatcherService;
    $service->dispatch($auditData, 'https://audits.local/storage/reports/test.pdf');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/webhook'
            && $request['auditId'] === 'test-audit-123'
            && $request['status'] === 'completed'
            && $request['score'] === 95
            && $request['pdfUrl'] === 'https://audits.local/storage/reports/test.pdf';
    });
});

it('does not dispatch webhook when url is empty', function (): void {
    Http::fake();

    config(['audits.webhook.return_url' => null]);

    $auditData = new AuditData(
        targetUrl: new Url('https://example.com'),
        score: new AuditScore(0.95),
        lcp: MetricValue::fromDisplayValue('0.6 s'),
        fcp: MetricValue::fromDisplayValue('0.5 s'),
        cls: MetricValue::fromDisplayValue('0.001'),
        auditId: 'test-audit-123',
    );

    $service = new WebhookDispatcherService;
    $service->dispatch($auditData, 'https://audits.local/storage/reports/test.pdf');

    Http::assertNothingSent();
});
