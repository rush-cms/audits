<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Cache;

final class IncrementScanCountAction
{
    public function execute(): void
    {
        $now = now();

        Cache::increment('scans:minute:'.$now->format('Y-m-d-H-i'));
        Cache::increment('scans:hour:'.$now->format('Y-m-d-H'));
        Cache::increment('scans:day:'.$now->format('Y-m-d'));
        Cache::increment('scans:month:'.$now->format('Y-m'));
    }
}
