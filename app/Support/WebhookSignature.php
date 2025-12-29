<?php

declare(strict_types=1);

namespace App\Support;

final class WebhookSignature
{
    public function __construct(
        private readonly ?string $secret = null
    ) {
        $this->secret = $secret ?? config('audits.webhook.secret');
    }

    public function generate(string $payload, int $timestamp): ?string
    {
        if ($this->secret === null || $this->secret === '' || $this->secret === '0') {
            return null;
        }

        $signedPayload = "{$timestamp}.{$payload}";

        return hash_hmac('sha256', $signedPayload, $this->secret);
    }

    public function verify(string $payload, int $timestamp, string $signature): bool
    {
        if ($this->secret === null || $this->secret === '' || $this->secret === '0') {
            return false;
        }

        if ($this->isTimestampExpired($timestamp)) {
            return false;
        }

        $expectedSignature = $this->generate($payload, $timestamp);

        return $expectedSignature !== null && hash_equals($expectedSignature, $signature);
    }

    public function generateHeaders(string $payload): array
    {
        $timestamp = time();
        $signature = $this->generate($payload, $timestamp);

        if ($signature === null) {
            return [];
        }

        $deliveryId = $this->generateDeliveryId();

        return [
            'X-Webhook-Signature' => "sha256={$signature}",
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-ID' => $deliveryId,
        ];
    }

    private function isTimestampExpired(int $timestamp, int $toleranceSeconds = 300): bool
    {
        $now = time();

        return abs($now - $timestamp) > $toleranceSeconds;
    }

    private function generateDeliveryId(): string
    {
        return sprintf(
            '%s-%s',
            date('YmdHis'),
            bin2hex(random_bytes(8))
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secret);
    }
}
