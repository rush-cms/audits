<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\WebhookPayloadData;
use App\Exceptions\WebhookDeliveryException;
use App\Models\Audit;
use App\Support\WebhookSignature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WebhookDispatcherService
{
    public function __construct(
        private readonly WebhookSignature $signature = new WebhookSignature
    ) {}

    public function dispatch(Audit $audit, string $pdfUrl): void
    {
        $webhookUrl = config('audits.webhook.return_url');

        if (empty($webhookUrl)) {
            Log::channel('webhooks')->debug('Webhook delivery skipped (no URL configured)', [
                'audit_id' => $audit->id,
            ]);

            return;
        }

        $startTime = microtime(true);
        $attempts = ($audit->webhook_attempts ?? 0) + 1;

        Log::channel('webhooks')->info('Dispatching webhook', [
            'audit_id' => $audit->id,
            'webhook_url' => $webhookUrl,
            'attempt' => $attempts,
        ]);

        $payload = WebhookPayloadData::fromAudit($audit, $pdfUrl);
        $payloadJson = json_encode($payload->toArray());

        if ($payloadJson === false) {
            Log::channel('webhooks')->error('Failed to encode webhook payload', [
                'audit_id' => $audit->id,
            ]);

            return;
        }

        try {
            $headers = $this->signature->generateHeaders($payloadJson);

            $response = Http::timeout(config('audits.webhook.timeout'))
                ->withHeaders($headers)
                ->post($webhookUrl, $payload->toArray());

            $duration = round((microtime(true) - $startTime) * 1000);

            $audit->update([
                'webhook_delivered_at' => now(),
                'webhook_status' => $response->status(),
                'webhook_attempts' => $attempts,
            ]);

            if ($response->successful()) {
                Log::channel('webhooks')->info('Webhook delivered successfully', [
                    'audit_id' => $audit->id,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'response_body' => $response->body(),
                ]);
            } else {
                Log::channel('webhooks')->warning('Webhook returned non-2xx status', [
                    'audit_id' => $audit->id,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'response_body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            $audit->update([
                'webhook_attempts' => $attempts,
            ]);

            Log::channel('webhooks')->error('Webhook delivery failed', [
                'audit_id' => $audit->id,
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'attempt' => $attempts,
            ]);

            throw new WebhookDeliveryException(
                "Failed to deliver webhook for audit {$audit->id}: {$e->getMessage()}",
                context: [
                    'audit_id' => $audit->id,
                    'webhook_url' => $webhookUrl,
                    'duration_ms' => $duration,
                    'attempts' => $attempts,
                    'original_error' => $e->getMessage(),
                ],
                previous: $e
            );
        }
    }
}
