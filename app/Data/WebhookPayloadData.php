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
        public readonly bool $screenshotsIncluded = true,
        public readonly ?string $screenshotError = null,
    ) {}

    public static function fromAudit(Audit $audit, string $pdfUrl): self
    {
        /** @var array<string, string> $metrics */
        $metrics = $audit->metrics ?? [];

        /** @var array<string, mixed>|null $screenshotsData */
        $screenshotsData = $audit->screenshots_data;

        $screenshotsIncluded = true;
        $screenshotError = null;

        if ($screenshotsData) {
            $hasDesktop = ! empty($screenshotsData['desktop']);
            $hasMobile = ! empty($screenshotsData['mobile']);
            $screenshotsIncluded = $hasDesktop || $hasMobile;
            $screenshotError = $screenshotsData['error'] ?? null;
        }

        return new self(
            auditId: $audit->id,
            status: $audit->status,
            targetUrl: $audit->url,
            pdfUrl: $pdfUrl,
            score: $audit->score ?? 0,
            metrics: new WebhookMetricsData(
                lcp: $metrics['lcp'] ?? 'N/A',
                fcp: $metrics['fcp'] ?? 'N/A',
                cls: $metrics['cls'] ?? 'N/A',
            ),
            strategy: $audit->strategy,
            lang: $audit->lang,
            screenshotsIncluded: $screenshotsIncluded,
            screenshotError: $screenshotError,
        );
    }
}
