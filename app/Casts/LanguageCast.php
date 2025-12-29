<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\Language;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class LanguageCast implements Cast
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Language
    {
        if ($value instanceof Language) {
            return $value;
        }

        if (is_string($value)) {
            return Language::fromString($value);
        }

        throw new \InvalidArgumentException('Cannot cast value to Language');
    }
}
