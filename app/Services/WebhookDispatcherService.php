<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuditData;
use App\Data\WebhookPayloadData;
use Illuminate\Support\Facades\Http;

final class WebhookDispatcherService
{
    public function dispatch(AuditData $auditData, string $pdfUrl): void
    {
        $webhookUrl = config('audits.webhook.return_url');

        if (empty($webhookUrl)) {
            return;
        }

        $payload = WebhookPayloadData::fromAuditData($auditData, $pdfUrl);

        Http::timeout(config('audits.webhook.timeout'))
            ->post($webhookUrl, $payload->toArray());
    }
}
