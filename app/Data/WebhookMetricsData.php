<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class WebhookMetricsData extends Data
{
    public function __construct(
        public readonly string $lcp,
        public readonly string $fcp,
        public readonly string $cls,
    ) {}
}
