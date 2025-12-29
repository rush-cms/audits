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

        $bothFailed = is_null($result['desktop']) && is_null($result['mobile']);
        $requireScreenshots = (bool) config('audits.require_screenshots', false);

        if ($bothFailed && $requireScreenshots) {
            $audit->recordStep('take_screenshots', 'failed', [
                'error' => $result['error'] ?? 'Both screenshots failed',
            ]);

            $audit->markAsFailed($result['error'] ?? 'Both screenshots failed');

            return;
        }

        $stepStatus = $bothFailed ? 'completed_with_warnings' : 'completed';
        $audit->recordStep('take_screenshots', $stepStatus, $bothFailed ? [
            'warning' => 'Screenshots failed, PDF will be generated without screenshots',
        ] : null);

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
            screenshotFailed: $bothFailed,
            screenshotError: $result['error'],
        );

        GenerateAuditPdfJob::dispatch($this->auditId, $updatedAuditData, $this->strategy, $this->lang);
    }

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if (! $audit) {
            return;
        }

        $requireScreenshots = (bool) config('audits.require_screenshots', false);

        $audit->recordStep('take_screenshots', 'failed', [
            'error' => $exception?->getMessage() ?? 'Failed to capture screenshots',
        ]);

        if ($requireScreenshots) {
            $audit->markAsFailed($exception?->getMessage() ?? 'Failed to capture screenshots');
        } else {
            $updatedAuditData = new AuditData(
                targetUrl: $this->auditData->targetUrl,
                score: $this->auditData->score,
                lcp: $this->auditData->lcp,
                fcp: $this->auditData->fcp,
                cls: $this->auditData->cls,
                auditId: $this->auditData->auditId,
                seo: $this->auditData->seo,
                accessibility: $this->auditData->accessibility,
                screenshotFailed: true,
                screenshotError: $exception?->getMessage(),
            );

            GenerateAuditPdfJob::dispatch($this->auditId, $updatedAuditData, $this->strategy, $this->lang);
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
}
