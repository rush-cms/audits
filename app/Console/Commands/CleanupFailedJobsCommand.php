<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CleanupFailedJobsCommand extends Command
{
    protected $signature = 'audits:cleanup-failed-jobs';

    protected $description = 'Cleanup failed jobs older than retention period';

    public function handle(): int
    {
        $retentionDays = (int) config('audits.failed_jobs_retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deleted} failed job(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
