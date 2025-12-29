<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Models\Audit;
use App\Services\ScreenshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class TakeScreenshotsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public readonly string $auditId,
        public readonly AuditData $auditData,
        public readonly string $strategy = 'mobile',
        public readonly string $lang = 'en',
    ) {}

    public function handle(ScreenshotService $screenshotService): void
    {
        $audit = Audit::findOrFail($this->auditId);

        $result = $screenshotService->capture(
            (string) $this->auditData->targetUrl,
            $this->auditData->auditId,
        );

        $audit->update([
            'screenshots_data' => $result,
        ]);

        $audit->recordStep('take_screenshots', 'completed');

        $updatedAuditData = new AuditData(
            targetUrl: $this->auditData->targetUrl,
            score: $this->auditData->score,
            lcp: $this->auditData->lcp,
            fcp: $this->auditData->fcp,
            cls: $this->auditData->cls,
            auditId: $this->auditData->auditId,
            seo: $this->auditData->seo,
            accessibility: $this->auditData->accessibility,
            desktopScreenshot: $result['desktop'],
            mobileScreenshot: $result['mobile'],
            screenshotFailed: $result['failed'],
            screenshotError: $result['error'],
        );

        GenerateAuditPdfJob::dispatch($this->auditId, $updatedAuditData, $this->strategy, $this->lang);
    }

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            $audit->recordStep('take_screenshots', 'failed', [
                'error' => $exception?->getMessage() ?? 'Failed to capture screenshots',
            ]);

            $audit->markAsFailed($exception?->getMessage() ?? 'Failed to capture screenshots');
        }
    }
}
