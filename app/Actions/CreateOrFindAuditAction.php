<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Audit;

final class CreateOrFindAuditAction
{
    public function execute(string $url, string $strategy, string $lang): Audit
    {
        $idempotencyKey = Audit::generateIdempotencyKey($url, $strategy);

        return Audit::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'url' => $url,
                'strategy' => $strategy,
                'lang' => $lang,
                'status' => 'pending',
            ]
        );
    }
}
