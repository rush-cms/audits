<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DispatchWebhookJob;
use App\Models\Audit;
use Illuminate\Console\Command;

final class RetryFailedWebhooksCommand extends Command
{
    protected $signature = 'webhook:retry-failed {--limit=50 : Maximum number of audits to retry}';

    protected $description = 'Retry webhook delivery for all completed audits with failed webhooks';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $failedAudits = Audit::query()
            ->where('status', 'completed')
            ->whereNotNull('pdf_path')
            ->whereNull('webhook_delivered_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($failedAudits->isEmpty()) {
            $this->info('No failed webhooks found.');

            return self::SUCCESS;
        }

        $this->info("Found {$failedAudits->count()} audits with failed webhooks.");

        $queued = 0;

        foreach ($failedAudits as $audit) {
            $pdfUrl = asset('storage/reports/'.basename((string) $audit->pdf_path));

            DispatchWebhookJob::dispatch($audit->id, $pdfUrl);

            $queued++;

            $this->line("Queued retry for audit {$audit->id} (attempts: {$audit->webhook_attempts})");
        }

        $this->info("Successfully queued {$queued} webhook retries.");

        return self::SUCCESS;
    }
}
