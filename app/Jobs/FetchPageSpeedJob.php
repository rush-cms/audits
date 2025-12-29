<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Exceptions\PageSpeedFetchException;
use App\Models\Audit;
use App\Services\PageSpeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FetchPageSpeedJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 90;

    public function __construct(
        public readonly string $auditId,
        public readonly string $url,
        public readonly string $strategy = 'mobile',
        public readonly string $lang = 'en',
    ) {}

    public function handle(PageSpeedService $pageSpeedService): void
    {
        $startTime = microtime(true);
        $audit = Audit::findOrFail($this->auditId);
        $audit->markAsProcessing();

        try {
            $lighthouseResult = $pageSpeedService->fetchLighthouseResult($this->url, $this->strategy);

            $auditData = AuditData::fromLighthouseResult($lighthouseResult);

            $audit->update([
                'pagespeed_data' => $auditData->toArray(),
            ]);

            $audit->recordStep('fetch_pagespeed', 'completed');

            $duration = round((microtime(true) - $startTime) * 1000);

            Log::channel('audits')->info('PageSpeed data fetched', [
                'audit_id' => $this->auditId,
                'url' => $this->url,
                'score' => $auditData->score,
                'duration_ms' => $duration,
            ]);

            TakeScreenshotsJob::dispatch($this->auditId, $auditData, $this->strategy, $this->lang);
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);

            Log::channel('audits')->error('PageSpeed fetch failed', [
                'audit_id' => $this->auditId,
                'url' => $this->url,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'will_retry' => $this->attempts() < $this->tries(),
            ]);

            throw new PageSpeedFetchException(
                "Failed to fetch PageSpeed data for {$this->url}: {$e->getMessage()}",
                context: [
                    'audit_id' => $this->auditId,
                    'url' => $this->url,
                    'strategy' => $this->strategy,
                    'duration_ms' => $duration,
                    'original_error' => $e->getMessage(),
                ],
                previous: $e
            );
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

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            $errorContext = [
                'exception' => $exception ? get_class($exception) : null,
                'file' => $exception?->getFile(),
                'line' => $exception?->getLine(),
                'url' => $this->url,
                'strategy' => $this->strategy,
                'attempts' => $this->attempts(),
            ];

            if ($exception instanceof PageSpeedFetchException) {
                $errorContext = array_merge($errorContext, $exception->context);
            }

            $audit->recordStep('fetch_pagespeed', 'failed', [
                'error' => $exception?->getMessage() ?? 'Failed to fetch PageSpeed data',
            ]);

            $audit->markAsFailed(
                $exception?->getMessage() ?? 'Failed to fetch PageSpeed data',
                $errorContext
            );

            Log::channel('audits')->critical('Job permanently failed', [
                'job' => 'FetchPageSpeedJob',
                'audit_id' => $this->auditId,
                'error' => $exception?->getMessage(),
                'context' => $errorContext,
            ]);
        }
    }
}
