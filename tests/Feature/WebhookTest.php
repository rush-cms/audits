<?php

declare(strict_types=1);

use App\Exceptions\WebhookDeliveryException;
use App\Jobs\DispatchWebhookJob;
use App\Mail\WebhookFailedNotification;
use App\Models\Audit;
use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

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

it('records webhook delivery history on success', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response(['acknowledged' => true], 200),
    ]);

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;
    $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf', 1);

    expect(WebhookDelivery::count())->toBe(1);

    $delivery = WebhookDelivery::first();
    expect($delivery->audit_id)->toBe($audit->id);
    expect($delivery->attempt_number)->toBe(1);
    expect($delivery->response_status)->toBe(200);
    expect($delivery->delivered_at)->not->toBeNull();
});

it('handles 4xx responses without retrying', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response(['error' => 'Bad request'], 400),
    ]);

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;
    $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf', 1);

    $delivery = WebhookDelivery::first();
    expect($delivery->response_status)->toBe(400);
    expect($delivery->delivered_at)->toBeNull();
});

it('throws exception on 5xx responses for retry', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response(['error' => 'Server error'], 500),
    ]);

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;

    expect(fn () => $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf', 1))
        ->toThrow(WebhookDeliveryException::class);

    $delivery = WebhookDelivery::first();
    expect($delivery->response_status)->toBe(500);
    expect($delivery->delivered_at)->toBeNull();
});

it('includes retry headers in webhook requests', function (): void {
    Http::fake();

    config(['audits.webhook.return_url' => 'https://example.com/webhook']);
    config(['audits.webhook.max_attempts' => 5]);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $service = new WebhookDispatcherService;
    $service->dispatch($audit, 'https://audits.local/storage/reports/test.pdf', 3);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Webhook-Attempt', '3')
            && $request->hasHeader('X-Webhook-Max-Attempts', '5');
    });
});

it('sends email notification on webhook permanent failure', function (): void {
    Mail::fake();

    config(['audits.notifications.enabled' => true]);
    config(['audits.notifications.admin_email' => 'admin@example.com']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $job = new DispatchWebhookJob($audit->id, 'https://audits.local/storage/reports/test.pdf');
    $job->failed(new Exception('Connection failed'));

    Mail::assertQueued(WebhookFailedNotification::class, function ($mail) use ($audit) {
        return $mail->audit->id === $audit->id;
    });
});

it('does not send notifications when disabled', function (): void {
    Mail::fake();

    config(['audits.notifications.enabled' => false]);
    config(['audits.notifications.admin_email' => 'admin@example.com']);

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $job = new DispatchWebhookJob($audit->id, 'https://audits.local/storage/reports/test.pdf');
    $job->failed(new Exception('Connection failed'));

    Mail::assertNotQueued(WebhookFailedNotification::class);
});

it('webhook retry command queues job for valid audit', function (): void {
    Queue::fake();

    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s', 'fcp' => '0.5 s', 'cls' => '0.001'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    $this->artisan('webhook:retry', ['audit_id' => $audit->id])
        ->assertSuccessful();

    Queue::assertPushed(DispatchWebhookJob::class, function ($job) use ($audit) {
        return $job->auditId === $audit->id;
    });
});

it('webhook retry-failed command queues jobs for failed webhooks', function (): void {
    Queue::fake();

    $failedAudit1 = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s'],
        'pdf_path' => 'reports/test1.pdf',
        'completed_at' => now(),
        'webhook_delivered_at' => null,
    ]);

    $failedAudit2 = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example2.com', 'mobile'),
        'url' => 'https://example2.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 85,
        'metrics' => ['lcp' => '0.8 s'],
        'pdf_path' => 'reports/test2.pdf',
        'completed_at' => now(),
        'webhook_delivered_at' => null,
    ]);

    $this->artisan('webhook:retry-failed')
        ->assertSuccessful();

    Queue::assertPushed(DispatchWebhookJob::class, 2);
});

it('prunes old webhook deliveries', function (): void {
    $audit = Audit::create([
        'idempotency_key' => Audit::generateIdempotencyKey('https://example.com', 'mobile'),
        'url' => 'https://example.com',
        'strategy' => 'mobile',
        'lang' => 'en',
        'status' => 'completed',
        'score' => 95,
        'metrics' => ['lcp' => '0.6 s'],
        'pdf_path' => 'reports/test.pdf',
        'completed_at' => now(),
    ]);

    WebhookDelivery::create([
        'audit_id' => $audit->id,
        'attempt_number' => 1,
        'url' => 'https://example.com/webhook',
        'payload' => ['test' => 'data'],
        'response_status' => 200,
        'delivered_at' => now()->subDays(35),
        'created_at' => now()->subDays(35),
    ]);

    WebhookDelivery::create([
        'audit_id' => $audit->id,
        'attempt_number' => 2,
        'url' => 'https://example.com/webhook',
        'payload' => ['test' => 'data'],
        'response_status' => 200,
        'delivered_at' => now()->subDays(15),
        'created_at' => now()->subDays(15),
    ]);

    expect(WebhookDelivery::count())->toBe(2);

    $this->artisan('webhook:prune-deliveries', ['--days' => 30])
        ->assertSuccessful();

    expect(WebhookDelivery::count())->toBe(1);
});
