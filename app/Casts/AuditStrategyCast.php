<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\AuditStrategy;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class AuditStrategyCast implements Cast
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): AuditStrategy
    {
        if ($value instanceof AuditStrategy) {
            return $value;
        }

        if (is_string($value)) {
            return AuditStrategy::fromString($value);
        }

        throw new \InvalidArgumentException('Cannot cast value to AuditStrategy');
    }
}
