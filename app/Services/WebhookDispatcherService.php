<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\WebhookPayloadData;
use App\Models\Audit;
use Illuminate\Support\Facades\Http;

final class WebhookDispatcherService
{
    public function dispatch(Audit $audit, string $pdfUrl): void
    {
        $webhookUrl = config('audits.webhook.return_url');

        if (empty($webhookUrl)) {
            return;
        }

        $payload = WebhookPayloadData::fromAudit($audit, $pdfUrl);

        Http::timeout(config('audits.webhook.timeout'))
            ->post($webhookUrl, $payload->toArray());
    }
}
