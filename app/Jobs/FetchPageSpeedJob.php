<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Services\PageSpeedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class FetchPageSpeedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(
        public readonly string $url,
        public readonly string $lang = 'en',
        public readonly string $strategy = 'mobile',
    ) {}

    public function handle(PageSpeedService $pageSpeedService): void
    {
        $lighthouseResult = $pageSpeedService->fetchLighthouseResult($this->url, $this->strategy);

        $auditData = AuditData::fromLighthouseResult($lighthouseResult);

        TakeScreenshotsJob::dispatch($auditData, $this->lang);
    }
}
