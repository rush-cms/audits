<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DispatchWebhookJob;
use App\Models\Audit;
use Illuminate\Console\Command;

final class RetryWebhookCommand extends Command
{
    protected $signature = 'webhook:retry {audit_id : The UUID of the audit to retry webhook delivery}';

    protected $description = 'Manually retry webhook delivery for a specific audit';

    public function handle(): int
    {
        $auditId = $this->argument('audit_id');

        $audit = Audit::find($auditId);

        if (! $audit) {
            $this->error("Audit {$auditId} not found.");

            return self::FAILURE;
        }

        if ($audit->status !== 'completed') {
            $this->error("Audit {$auditId} is not completed (status: {$audit->status}).");

            return self::FAILURE;
        }

        if (! $audit->pdf_path) {
            $this->error("Audit {$auditId} has no PDF generated.");

            return self::FAILURE;
        }

        $pdfUrl = asset('storage/reports/'.basename($audit->pdf_path));

        DispatchWebhookJob::dispatch($audit->id, $pdfUrl);

        $this->info("Webhook retry queued for audit {$auditId}");
        $this->line("Current webhook attempts: {$audit->webhook_attempts}");
        $this->line("PDF URL: {$pdfUrl}");

        return self::SUCCESS;
    }
}
