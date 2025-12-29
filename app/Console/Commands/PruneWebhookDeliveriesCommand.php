<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

final class PruneWebhookDeliveriesCommand extends Command
{
    protected $signature = 'webhook:prune-deliveries {--days=30 : Number of days to retain webhook delivery records}';

    protected $description = 'Delete old webhook delivery records to save database space';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning webhook deliveries older than {$days} days (before {$cutoffDate->toDateTimeString()})...");

        $deleted = WebhookDelivery::query()
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        if ($deleted === 0) {
            $this->info('No webhook deliveries to prune.');

            return self::SUCCESS;
        }

        $this->info("Successfully pruned {$deleted} webhook delivery records.");

        return self::SUCCESS;
    }
}
