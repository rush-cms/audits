<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Exceptions\PdfGenerationException;
use App\Models\Audit;
use App\Services\PdfGeneratorService;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GenerateAuditPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(
        public readonly string $auditId,
        public readonly AuditData $auditData,
        public readonly string $strategy = 'mobile',
        public readonly string $lang = 'en',
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->auditId),
            new RateLimited('pdf-generation'),
        ];
    }

    public function handle(
        PdfGeneratorService $pdfGenerator,
        WebhookDispatcherService $webhookDispatcher,
    ): void {
        $startTime = microtime(true);
        $audit = Audit::findOrFail($this->auditId);

        try {
            $pdfPath = $pdfGenerator->generate($this->auditData, $this->lang);
            $pdfUrl = $pdfGenerator->getPublicUrl($pdfPath);

            $pdfSize = file_exists($pdfPath) ? filesize($pdfPath) : 0;
            $duration = round((microtime(true) - $startTime) * 1000);

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

            $this->deleteScreenshotsIfConfigured();

            Log::channel('audits')->info('Audit completed', [
                'audit_id' => $this->auditId,
                'score' => $this->auditData->score->toPercentage(),
                'pdf_size' => $pdfSize,
                'duration_ms' => $duration,
            ]);

            $webhookDispatcher->dispatch($audit, $pdfUrl);
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            Log::channel('audits')->error('PDF generation failed', [
                'audit_id' => $this->auditId,
                'url' => (string) $this->auditData->targetUrl,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'will_retry' => $this->attempts() < $this->tries(),
            ]);

            throw new PdfGenerationException(
                "Failed to generate PDF for {$this->auditData->targetUrl}: {$e->getMessage()}",
                context: [
                    'audit_id' => $this->auditId,
                    'url' => (string) $this->auditData->targetUrl,
                    'score' => $this->auditData->score->toPercentage(),
                    'duration_ms' => $duration,
                    'original_error' => $e->getMessage(),
                ],
                previous: $e
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            $errorContext = [
                'exception' => $exception ? get_class($exception) : null,
                'file' => $exception?->getFile(),
                'line' => $exception?->getLine(),
                'url' => (string) $this->auditData->targetUrl,
                'score' => $this->auditData->score->toPercentage(),
                'attempts' => $this->attempts(),
            ];

            if ($exception instanceof PdfGenerationException) {
                $errorContext = array_merge($errorContext, $exception->context);
            }

            $audit->recordStep('generate_pdf', 'failed', [
                'error' => $exception?->getMessage() ?? 'Failed to generate PDF',
            ]);

            $audit->markAsFailed(
                $exception?->getMessage() ?? 'Failed to generate PDF',
                $errorContext
            );

            Log::channel('audits')->critical('Job permanently failed', [
                'job' => 'GenerateAuditPdfJob',
                'audit_id' => $this->auditId,
                'error' => $exception?->getMessage(),
                'context' => $errorContext,
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $base = (int) config('audits.job_backoff_base', 30);

        return [$base, $base * 2];
    }

    public function tries(): int
    {
        return (int) config('audits.job_max_attempts', 3);
    }

    private function deleteScreenshotsIfConfigured(): void
    {
        if (! config('audits.screenshots.delete_after_pdf', true)) {
            return;
        }

        $deleted = 0;

        if ($this->auditData->desktopScreenshot && file_exists($this->auditData->desktopScreenshot)) {
            unlink($this->auditData->desktopScreenshot);
            $deleted++;
        }

        if ($this->auditData->mobileScreenshot && file_exists($this->auditData->mobileScreenshot)) {
            unlink($this->auditData->mobileScreenshot);
            $deleted++;
        }

        if ($deleted > 0) {
            Log::channel('audits')->debug('Screenshots deleted after PDF generation', [
                'audit_id' => $this->auditId,
                'deleted_count' => $deleted,
            ]);
        }
    }
}
