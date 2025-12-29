<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

final class CreateOrFindAuditAction
{
    private const MAX_ATTEMPTS = 3;

    public function execute(string $url, string $strategy, string $lang): Audit
    {
        $idempotencyKey = Audit::generateIdempotencyKey($url, $strategy);
        $attempt = 0;

        while ($attempt < self::MAX_ATTEMPTS) {
            try {
                return Audit::firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'url' => $url,
                        'strategy' => $strategy,
                        'lang' => $lang,
                        'status' => 'pending',
                    ]
                );
            } catch (QueryException $e) {
                if ($this->isDuplicateKeyError($e)) {
                    $attempt++;

                    if ($attempt >= self::MAX_ATTEMPTS) {
                        return Audit::where('idempotency_key', $idempotencyKey)->firstOrFail();
                    }

                    $backoffMs = 10 * (2 ** ($attempt - 1));
                    usleep($backoffMs * 1000);

                    Log::info('Race condition detected, retrying', [
                        'attempt' => $attempt,
                        'backoff_ms' => $backoffMs,
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    continue;
                }

                throw $e;
            }
        }

        return Audit::where('idempotency_key', $idempotencyKey)->firstOrFail();
    }

    private function isDuplicateKeyError(QueryException $e): bool
    {
        return $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry');
    }
}
