<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\WebhookFailedNotification;
use App\Models\Audit;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        if (! $audit) {
            return;
        }

        Log::channel('webhooks')->critical('Webhook permanently failed after all retries', [
            'audit_id' => $this->auditId,
            'pdf_url' => $this->pdfUrl,
            'attempts' => $this->attempts(),
            'error' => $exception?->getMessage(),
        ]);

        if (! config('audits.notifications.enabled', true)) {
            return;
        }

        $this->sendEmailNotification($audit);
        $this->sendSlackNotification($audit, $exception);
    }

    private function sendEmailNotification(Audit $audit): void
    {
        $adminEmail = config('audits.notifications.admin_email');

        if (empty($adminEmail)) {
            return;
        }

        $cacheKey = "webhook_failure_email_sent:{$audit->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            Mail::to($adminEmail)->queue(new WebhookFailedNotification($audit, $this->attempts()));

            Cache::put($cacheKey, true, now()->addHour());

            Log::channel('webhooks')->info('Webhook failure email notification sent', [
                'audit_id' => $audit->id,
                'admin_email' => $adminEmail,
            ]);
        } catch (Throwable $e) {
            Log::channel('webhooks')->error('Failed to send webhook failure email', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendSlackNotification(Audit $audit, ?Throwable $exception): void
    {
        $slackWebhookUrl = config('audits.notifications.slack_webhook_url');

        if (empty($slackWebhookUrl)) {
            return;
        }

        $cacheKey = "webhook_failure_slack_sent:{$audit->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            Http::timeout(5)->post($slackWebhookUrl, [
                'text' => "Webhook delivery permanently failed for audit {$audit->id}",
                'attachments' => [
                    [
                        'fallback' => "Webhook failed for audit {$audit->id}",
                        'color' => 'danger',
                        'fields' => [
                            ['title' => 'Audit ID', 'value' => $audit->id, 'short' => true],
                            ['title' => 'URL', 'value' => $audit->url, 'short' => false],
                            ['title' => 'Attempts', 'value' => (string) $this->attempts(), 'short' => true],
                            ['title' => 'Score', 'value' => (string) $audit->score, 'short' => true],
                            ['title' => 'Error', 'value' => $exception?->getMessage() ?? 'Unknown error', 'short' => false],
                            ['title' => 'PDF URL', 'value' => $this->pdfUrl, 'short' => false],
                        ],
                    ],
                ],
            ]);

            Cache::put($cacheKey, true, now()->addHour());

            Log::channel('webhooks')->info('Webhook failure Slack notification sent', [
                'audit_id' => $audit->id,
            ]);
        } catch (Throwable $e) {
            Log::channel('webhooks')->error('Failed to send webhook failure Slack notification', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
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
