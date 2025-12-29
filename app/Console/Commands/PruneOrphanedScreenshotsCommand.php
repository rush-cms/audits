<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class PruneOrphanedScreenshotsCommand extends Command
{
    protected $signature = 'audits:prune-orphaned-screenshots';

    protected $description = 'Delete orphaned screenshots older than configured retention hours';

    public function handle(): int
    {
        $screenshotsPath = storage_path('app/public/screenshots');

        if (! is_dir($screenshotsPath)) {
            $this->info('Screenshots directory does not exist.');

            return self::SUCCESS;
        }

        $retentionHours = (int) config('audits.screenshots.orphaned_retention_hours', 24);
        $cutoffTime = now()->subHours($retentionHours)->timestamp;

        $files = File::files($screenshotsPath);
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $filePath = $file->getRealPath();

            if (! $filePath || ! file_exists($filePath)) {
                continue;
            }

            $fileTime = filemtime($filePath);

            if ($fileTime === false || $fileTime >= $cutoffTime) {
                continue;
            }

            $fileSize = filesize($filePath);
            if ($fileSize === false) {
                $fileSize = 0;
            }

            $totalSize += $fileSize;

            if (unlink($filePath)) {
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} orphaned screenshots (".number_format($totalSize / 1024 / 1024, 2).' MB)');
        } else {
            $this->info('No orphaned screenshots found.');
        }

        return self::SUCCESS;
    }
}
