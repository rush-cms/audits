<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Models\Audit;
use App\Services\PdfGeneratorService;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class GenerateAuditPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $auditId,
        public readonly AuditData $auditData,
        public readonly string $strategy = 'mobile',
        public readonly string $lang = 'en',
    ) {}

    public function handle(
        PdfGeneratorService $pdfGenerator,
        WebhookDispatcherService $webhookDispatcher,
    ): void {
        $audit = Audit::findOrFail($this->auditId);

        $pdfPath = $pdfGenerator->generate($this->auditData, $this->lang);
        $pdfUrl = $pdfGenerator->getPublicUrl($pdfPath);

        $metrics = [
            'lcp' => $this->auditData->lcp->format(),
            'fcp' => $this->auditData->fcp->format(),
            'cls' => $this->auditData->cls->format(),
        ];

        $audit->recordStep('generate_pdf', 'completed');

        $audit->markAsCompleted(
            score: $this->auditData->score->toPercentage(),
            metrics: $metrics,
            pdfPath: $pdfPath,
        );

        $webhookDispatcher->dispatch($audit, $pdfUrl);
    }

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            $audit->recordStep('generate_pdf', 'failed', [
                'error' => $exception?->getMessage() ?? 'Failed to generate PDF',
            ]);

            $audit->markAsFailed($exception?->getMessage() ?? 'Failed to generate PDF');
        }
    }
}
