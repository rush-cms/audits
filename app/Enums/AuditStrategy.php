<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditStrategy: string
{
    case Mobile = 'mobile';
    case Desktop = 'desktop';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'mobile' => self::Mobile,
            'desktop' => self::Desktop,
            default => self::Mobile,
        };
    }

    public function toString(): string
    {
        return $this->value;
    }
}
