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
            $html = '<html><body><h1>Test</h1></body></html>';

            Browsershot::html($html)
                ->setNodeBinary(config('audits.browsershot.node_binary'))
                ->setNpmBinary(config('audits.browsershot.npm_binary'))
                ->setChromePath(config('audits.browsershot.chrome_path'))
                ->pdf();

            $this->info('✓ Browsershot is working correctly!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Browsershot failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
