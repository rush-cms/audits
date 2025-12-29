<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Audit;
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
        public readonly string $strategy,
        public readonly string $lang,
    ) {}

    public static function fromAudit(Audit $audit, string $pdfUrl): self
    {
        return new self(
            auditId: $audit->id,
            status: $audit->status,
            targetUrl: $audit->url,
            pdfUrl: $pdfUrl,
            score: $audit->score ?? 0,
            metrics: new WebhookMetricsData(
                lcp: $audit->metrics['lcp'] ?? 'N/A',
                fcp: $audit->metrics['fcp'] ?? 'N/A',
                cls: $audit->metrics['cls'] ?? 'N/A',
            ),
            strategy: $audit->strategy,
            lang: $audit->lang,
        );
    }
}
