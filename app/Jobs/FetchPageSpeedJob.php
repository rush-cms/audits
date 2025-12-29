<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Models\Audit;
use App\Services\PageSpeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        $audit = Audit::findOrFail($this->auditId);
        $audit->markAsProcessing();

        $lighthouseResult = $pageSpeedService->fetchLighthouseResult($this->url, $this->strategy);

        $auditData = AuditData::fromLighthouseResult($lighthouseResult);

        $audit->update([
            'pagespeed_data' => $auditData->toArray(),
        ]);

        $audit->recordStep('fetch_pagespeed', 'completed');

        TakeScreenshotsJob::dispatch($this->auditId, $auditData, $this->strategy, $this->lang);
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
            $audit->recordStep('fetch_pagespeed', 'failed', [
                'error' => $exception?->getMessage() ?? 'Failed to fetch PageSpeed data',
            ]);

            $audit->markAsFailed($exception?->getMessage() ?? 'Failed to fetch PageSpeed data');
        }
    }
}
