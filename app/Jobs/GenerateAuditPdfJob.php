<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\AuditData;
use App\Services\PdfGeneratorService;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateAuditPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly AuditData $auditData,
        public readonly string $lang = 'en',
    ) {}

    public function handle(
        PdfGeneratorService $pdfGenerator,
        WebhookDispatcherService $webhookDispatcher,
    ): void {
        $pdfPath = $pdfGenerator->generate($this->auditData, $this->lang);
        $pdfUrl = $pdfGenerator->getPublicUrl($pdfPath);

        $webhookDispatcher->dispatch($this->auditData, $pdfUrl);
    }
}
