<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class PrunePdfsCommand extends Command
{
    protected $signature = 'audit:prune-pdfs {--days= : Number of days to retain (default from config)}';

    protected $description = 'Remove old PDF reports';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('audits.pdf.retention_days', 7));
        $cutoffTime = now()->subDays($days)->timestamp;

        $this->info("Pruning PDFs older than {$days} days...");

        $disk = Storage::disk('public');
        $directory = 'reports';

        if (! $disk->exists($directory)) {
            $this->info('No reports directory found. Nothing to prune.');

            return self::SUCCESS;
        }

        $files = $disk->files($directory);
        $deleted = 0;

        foreach ($files as $file) {
            if ($disk->lastModified($file) < $cutoffTime) {
                $disk->delete($file);
                $deleted++;
                $this->line("Deleted: {$file}");
            }
        }

        $this->newLine();
        $this->info("Pruning complete. {$deleted} file(s) deleted.");

        return self::SUCCESS;
    }
}
