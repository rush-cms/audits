<?php

declare(strict_types=1);

namespace App\Enums;

enum Language: string
{
    case English = 'en';
    case PortugueseBR = 'pt_BR';
    case Spanish = 'es';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'en' => self::English,
            'pt_BR' => self::PortugueseBR,
            'es' => self::Spanish,
            default => self::English,
        };
    }

    public function toString(): string
    {
        return $this->value;
    }
}
