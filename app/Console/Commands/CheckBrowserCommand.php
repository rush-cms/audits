<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

final class CheckBrowserCommand extends Command
{
    protected $signature = 'audit:check-browser';

    protected $description = 'Verify that Puppeteer/Chrome is properly configured';

    public function handle(): int
    {
        $this->info('Checking browser configuration...');
        $this->newLine();

        $this->line('Node: '.config('audits.browsershot.node_binary'));
        $this->line('NPM: '.config('audits.browsershot.npm_binary'));
        $this->line('Chrome: '.config('audits.browsershot.chrome_path'));
        $this->newLine();

        try {
            $tempPath = storage_path('app/browsershot-test.pdf');

            Browsershot::url('https://example.com')
                ->setNodeBinary(config('audits.browsershot.node_binary'))
                ->setNpmBinary(config('audits.browsershot.npm_binary'))
                ->setChromePath(config('audits.browsershot.chrome_path'))
                ->noSandbox()
                ->save($tempPath);

            if (file_exists($tempPath)) {
                $size = filesize($tempPath);
                unlink($tempPath);
                $this->info("✓ Browsershot is working correctly! (PDF: {$size} bytes)");

                return self::SUCCESS;
            }

            $this->error('✗ PDF was not created');

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('✗ Browsershot failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
