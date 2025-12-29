<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Exceptions\ScreenshotCaptureException;
use App\Models\Audit;
use App\Services\ScreenshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
        $startTime = microtime(true);
        $audit = Audit::findOrFail($this->auditId);

        try {
            $result = $screenshotService->capture(
                (string) $this->auditData->targetUrl,
                $this->auditData->auditId,
            );

            $audit->update([
                'screenshots_data' => $result,
            ]);

            $bothFailed = is_null($result['desktop']) && is_null($result['mobile']);
            $requireScreenshots = (bool) config('audits.require_screenshots', false);

            $duration = round((microtime(true) - $startTime) * 1000);

            if ($bothFailed && $requireScreenshots) {
                Log::channel('audits')->error('Screenshots failed (required)', [
                    'audit_id' => $this->auditId,
                    'error' => $result['error'] ?? 'Both screenshots failed',
                    'duration_ms' => $duration,
                ]);

                $audit->recordStep('take_screenshots', 'failed', [
                    'error' => $result['error'] ?? 'Both screenshots failed',
                ]);

                $audit->markAsFailed(
                    $result['error'] ?? 'Both screenshots failed',
                    [
                        'desktop_failed' => is_null($result['desktop']),
                        'mobile_failed' => is_null($result['mobile']),
                        'error' => $result['error'],
                    ]
                );

                return;
            }

            if ($bothFailed) {
                Log::channel('audits')->warning('Screenshots failed (continuing)', [
                    'audit_id' => $this->auditId,
                    'error' => $result['error'],
                    'duration_ms' => $duration,
                ]);
            } else {
                Log::channel('audits')->info('Screenshots captured', [
                    'audit_id' => $this->auditId,
                    'desktop_size' => $result['desktop'] ? strlen($result['desktop']) : 0,
                    'mobile_size' => $result['mobile'] ? strlen($result['mobile']) : 0,
                    'duration_ms' => $duration,
                ]);
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
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            Log::channel('audits')->error('Screenshot capture failed', [
                'audit_id' => $this->auditId,
                'url' => (string) $this->auditData->targetUrl,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'will_retry' => $this->attempts() < $this->tries(),
            ]);

            throw new ScreenshotCaptureException(
                "Failed to capture screenshots for {$this->auditData->targetUrl}: {$e->getMessage()}",
                context: [
                    'audit_id' => $this->auditId,
                    'url' => (string) $this->auditData->targetUrl,
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

        if (! $audit) {
            return;
        }

        $requireScreenshots = (bool) config('audits.require_screenshots', false);

        $errorContext = [
            'exception' => $exception ? get_class($exception) : null,
            'file' => $exception?->getFile(),
            'line' => $exception?->getLine(),
            'url' => (string) $this->auditData->targetUrl,
            'attempts' => $this->attempts(),
            'require_screenshots' => $requireScreenshots,
        ];

        if ($exception instanceof ScreenshotCaptureException) {
            $errorContext = array_merge($errorContext, $exception->context);
        }

        $audit->recordStep('take_screenshots', 'failed', [
            'error' => $exception?->getMessage() ?? 'Failed to capture screenshots',
        ]);

        if ($requireScreenshots) {
            $audit->markAsFailed(
                $exception?->getMessage() ?? 'Failed to capture screenshots',
                $errorContext
            );

            Log::channel('audits')->critical('Job permanently failed', [
                'job' => 'TakeScreenshotsJob',
                'audit_id' => $this->auditId,
                'error' => $exception?->getMessage(),
                'context' => $errorContext,
            ]);
        } else {
            Log::channel('audits')->warning('Screenshots failed, continuing with PDF generation', [
                'audit_id' => $this->auditId,
                'error' => $exception?->getMessage(),
            ]);

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
