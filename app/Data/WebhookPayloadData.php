<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class WebhookPayloadData extends Data
{
    public function __construct(
        public readonly string $auditId,
        public readonly string $status,
        public readonly string $targetUrl,
        public readonly string $pdfUrl,
        public readonly int $score,
        public readonly WebhookMetricsData $metrics,
    ) {}

    public static function fromAuditData(AuditData $audit, string $pdfUrl): self
    {
        return new self(
            auditId: $audit->auditId,
            status: 'completed',
            targetUrl: (string) $audit->targetUrl,
            pdfUrl: $pdfUrl,
            score: $audit->score->toPercentage(),
            metrics: new WebhookMetricsData(
                lcp: $audit->lcp->format(),
                fcp: $audit->fcp->format(),
                cls: $audit->cls->format(),
            ),
        );
    }
}
