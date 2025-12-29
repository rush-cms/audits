<?php

declare(strict_types=1);

use App\Models\Audit;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('dispatches webhook with correct payload including strategy and lang', function (): void {
    Http::fake();

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'pt_BR',
        'status' => 'completed',
        'score' => 95,
        'metrics' => [
            'lcp' => '0.6 s',
            'fcp' => '0.5 s',
            'cls' => '0.001',
        ],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;
    $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf');

    Http::assertSent(function ($request) use ($audit) {
        return $request->url() === 'https://example.com/webhook'
            && $request['auditId'] === $audit->id
            && $request['status'] === 'completed'
            && $request['score'] === 95
            && $request['strategy'] === 'mobile'
            && $request['lang'] === 'pt_BR'
            && $request['targetUrl'] === 'https://example.com'
            && $request['pdfUrl'] === 'https://audits.local/storage/reports/test.pdf';
    });
});

it('does not dispatch webhook when url is empty', function (): void {
    Http::fake();

    config(['audits.webhook.return_url' => null]);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => [
            'lcp' => '0.6 s',
            'fcp' => '0.5 s',
            'cls' => '0.001',
        ],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;
    $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf');

    Http::assertNothingSent();
});
