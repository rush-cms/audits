<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExplainQueriesCommand extends Command
{
    protected $signature = 'audits:explain-queries';

    protected $description = 'Analyze common queries to verify index usage';

    public function handle(): int
    {
        $this->info('Analyzing common queries...');
        $this->newLine();

        $queries = [
            'Find pending/processing audits by URL and strategy' => "SELECT * FROM audits WHERE url = 'https://example.com' AND strategy = 'mobile' AND status IN ('pending', 'processing') ORDER BY created_at DESC LIMIT 1",
            'Find completed audits by URL and strategy' => "SELECT * FROM audits WHERE url = 'https://example.com' AND strategy = 'mobile' AND status = 'completed' ORDER BY created_at DESC LIMIT 1",
            'Find recent failed audits' => "SELECT * FROM audits WHERE status = 'failed' ORDER BY created_at DESC LIMIT 10",
            'Count audits by status' => 'SELECT status, COUNT(*) as total FROM audits GROUP BY status',
        ];

        foreach ($queries as $description => $query) {
            $this->info("Query: {$description}");
            $this->line($query);
            $this->newLine();

            $explain = DB::select("EXPLAIN {$query}");

            $this->table(
                ['id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'Extra'],
                collect($explain)->map(fn ($row) => (array) $row)->toArray()
            );

            $this->newLine();
        }

        $this->info('Analysis complete!');

        return self::SUCCESS;
    }
}
