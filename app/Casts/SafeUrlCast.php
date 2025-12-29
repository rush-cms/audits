<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\SafeUrl;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class SafeUrlCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): SafeUrl
    {
        if ($value instanceof SafeUrl) {
            return $value;
        }

        if (is_string($value)) {
            return SafeUrl::from($value);
        }

        throw new \InvalidArgumentException('Cannot cast value to SafeUrl');
    }
}
