<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Audit;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public function __construct(
        public readonly string $auditId,
        public readonly string $pdfUrl
    ) {}

    public function handle(WebhookDispatcherService $dispatcher): void
    {
        $audit = Audit::findOrFail($this->auditId);

        $dispatcher->dispatch($audit, $this->pdfUrl, $this->attempts());
    }

    public function failed(?Throwable $exception): void
    {
        $audit = Audit::find($this->auditId);

        if ($audit) {
            Log::channel('webhooks')->critical('Webhook permanently failed after all retries', [
                'audit_id' => $this->auditId,
                'pdf_url' => $this->pdfUrl,
                'attempts' => $this->attempts(),
                'error' => $exception?->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $base = [30, 60, 300, 900];
        $backoff = [];

        foreach ($base as $delay) {
            $jitter = (int) ($delay * 0.2 * (mt_rand(0, 100) / 100));
            $backoff[] = $delay + $jitter;
        }

        return $backoff;
    }

    public function tries(): int
    {
        return config('audits.webhook.max_attempts', 5);
    }
}
