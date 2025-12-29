<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\WebhookPayloadData;
use App\Exceptions\WebhookDeliveryException;
use App\Models\Audit;
use App\Models\WebhookDelivery;
use App\Support\WebhookSignature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WebhookDispatcherService
{
    public function __construct(
        private readonly WebhookSignature $signature = new WebhookSignature
    ) {}

    public function dispatch(Audit $audit, string $pdfUrl, int $currentAttempt = 1): void
    {
        $webhookUrl = config('audits.webhook.return_url');

        if (empty($webhookUrl)) {
            Log::channel('webhooks')->debug('Webhook delivery skipped (no URL configured)', [
                'audit_id' => $audit->id,
            ]);

            return;
        }

        $startTime = microtime(true);
        $maxAttempts = config('audits.webhook.max_attempts', 5);

        Log::channel('webhooks')->info('Dispatching webhook', [
            'audit_id' => $audit->id,
            'webhook_url' => $webhookUrl,
            'attempt' => $currentAttempt,
            'max_attempts' => $maxAttempts,
        ]);

        $payload = WebhookPayloadData::fromAudit($audit, $pdfUrl);
        $payloadJson = json_encode($payload->toArray());

        if ($payloadJson === false) {
            Log::channel('webhooks')->error('Failed to encode webhook payload', [
                'audit_id' => $audit->id,
            ]);

            return;
        }

        $responseStatus = null;
        $responseBody = null;
        $errorMessage = null;

        try {
            $headers = $this->signature->generateHeaders($payloadJson);
            $headers['X-Webhook-Attempt'] = (string) $currentAttempt;
            $headers['X-Webhook-Max-Attempts'] = (string) $maxAttempts;

            $response = Http::timeout(config('audits.webhook.timeout', 5))
                ->connectTimeout(config('audits.webhook.connect_timeout', 2))
                ->withHeaders($headers)
                ->post($webhookUrl, $payload->toArray());

            $duration = round((microtime(true) - $startTime) * 1000);
            $responseStatus = $response->status();
            $responseBody = $response->body();

            $this->recordDelivery(
                audit: $audit,
                attemptNumber: $currentAttempt,
                url: $webhookUrl,
                payload: $payload->toArray(),
                responseStatus: $responseStatus,
                responseBody: $responseBody,
                responseTimeMs: (int) $duration,
                delivered: $response->successful()
            );

            $audit->update([
                'webhook_attempts' => $currentAttempt,
            ]);

            if ($response->successful()) {
                $audit->update([
                    'webhook_delivered_at' => now(),
                    'webhook_status' => $responseStatus,
                ]);

                Log::channel('webhooks')->info('Webhook delivered successfully', [
                    'audit_id' => $audit->id,
                    'status' => $responseStatus,
                    'duration_ms' => $duration,
                ]);

                return;
            }

            if ($response->clientError()) {
                Log::channel('webhooks')->error('Webhook rejected by client (4xx)', [
                    'audit_id' => $audit->id,
                    'status' => $responseStatus,
                    'duration_ms' => $duration,
                    'response_body' => $responseBody,
                ]);

                $this->fail();

                return;
            }

            if ($response->serverError()) {
                Log::channel('webhooks')->warning('Webhook server error (5xx), will retry', [
                    'audit_id' => $audit->id,
                    'status' => $responseStatus,
                    'duration_ms' => $duration,
                    'response_body' => $responseBody,
                ]);

                throw new WebhookDeliveryException(
                    "Server error {$responseStatus}",
                    context: [
                        'audit_id' => $audit->id,
                        'status' => $responseStatus,
                        'response_body' => $responseBody,
                    ]
                );
            }
        } catch (ConnectionException $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            $this->recordDelivery(
                audit: $audit,
                attemptNumber: $currentAttempt,
                url: $webhookUrl,
                payload: $payload->toArray(),
                responseTimeMs: (int) $duration,
                errorMessage: $errorMessage,
                delivered: false
            );

            $audit->update([
                'webhook_attempts' => $currentAttempt,
            ]);

            Log::channel('webhooks')->error('Webhook connection failed', [
                'audit_id' => $audit->id,
                'webhook_url' => $webhookUrl,
                'error' => $errorMessage,
                'duration_ms' => $duration,
                'attempt' => $currentAttempt,
            ]);

            throw new WebhookDeliveryException(
                "Connection failed: {$errorMessage}",
                context: [
                    'audit_id' => $audit->id,
                    'webhook_url' => $webhookUrl,
                    'duration_ms' => $duration,
                    'attempts' => $currentAttempt,
                ],
                previous: $e
            );
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            $this->recordDelivery(
                audit: $audit,
                attemptNumber: $currentAttempt,
                url: $webhookUrl,
                payload: $payload->toArray(),
                responseTimeMs: (int) $duration,
                errorMessage: $errorMessage,
                delivered: false
            );

            $audit->update([
                'webhook_attempts' => $currentAttempt,
            ]);

            Log::channel('webhooks')->error('Webhook delivery failed', [
                'audit_id' => $audit->id,
                'webhook_url' => $webhookUrl,
                'error' => $errorMessage,
                'duration_ms' => $duration,
                'attempt' => $currentAttempt,
            ]);

            throw new WebhookDeliveryException(
                "Failed to deliver webhook: {$errorMessage}",
                context: [
                    'audit_id' => $audit->id,
                    'webhook_url' => $webhookUrl,
                    'duration_ms' => $duration,
                    'attempts' => $currentAttempt,
                    'original_error' => $errorMessage,
                ],
                previous: $e
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordDelivery(
        Audit $audit,
        int $attemptNumber,
        string $url,
        array $payload,
        ?int $responseStatus = null,
        ?string $responseBody = null,
        ?int $responseTimeMs = null,
        ?string $errorMessage = null,
        bool $delivered = false
    ): void {
        WebhookDelivery::create([
            'audit_id' => $audit->id,
            'attempt_number' => $attemptNumber,
            'url' => $url,
            'payload' => $payload,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'delivered_at' => $delivered ? now() : null,
            'created_at' => now(),
        ]);
    }
}
