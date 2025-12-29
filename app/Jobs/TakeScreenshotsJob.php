<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Services\ScreenshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class TakeScreenshotsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public readonly AuditData $auditData,
        public readonly string $lang = 'en',
    ) {}

    public function handle(ScreenshotService $screenshotService): void
    {
        $result = $screenshotService->capture(
            (string) $this->auditData->targetUrl,
            $this->auditData->auditId,
        );

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

        GenerateAuditPdfJob::dispatch($updatedAuditData, $this->lang);
    }
}
