<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\WebhookPayloadData;
use App\Models\Audit;
use App\Support\WebhookSignature;
use Illuminate\Support\Facades\Http;

final class WebhookDispatcherService
{
    public function __construct(
        private readonly WebhookSignature $signature = new WebhookSignature()
    ) {}

    public function dispatch(Audit $audit, string $pdfUrl): void
    {
        $webhookUrl = config('audits.webhook.return_url');

        if (empty($webhookUrl)) {
            return;
        }

        $payload = WebhookPayloadData::fromAudit($audit, $pdfUrl);
        $payloadJson = json_encode($payload->toArray());

        if ($payloadJson === false) {
            return;
        }

        $headers = $this->signature->generateHeaders($payloadJson);

        Http::timeout(config('audits.webhook.timeout'))
            ->withHeaders($headers)
            ->post($webhookUrl, $payload->toArray());
    }
}
