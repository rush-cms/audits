<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class AuditScore
{
    private const float PASSING_THRESHOLD = 0.9;

    private const float WARNING_THRESHOLD = 0.5;

    public function __construct(
        private float $value,
    ) {
        if ($value < 0.0 || $value > 1.0) {
            throw new \InvalidArgumentException("Score must be between 0.0 and 1.0, got: {$value}");
        }
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function toPercentage(): int
    {
        return (int) round($this->value * 100);
    }

    public function getColor(): string
    {
        if ($this->value >= self::PASSING_THRESHOLD) {
            return 'green';
        }

        if ($this->value >= self::WARNING_THRESHOLD) {
            return 'orange';
        }

        return 'red';
    }

    public function isPassing(): bool
    {
        return $this->value >= self::PASSING_THRESHOLD;
    }

    public function getLabel(): string
    {
        if ($this->isPassing()) {
            return 'Good';
        }

        if ($this->value >= self::WARNING_THRESHOLD) {
            return 'Needs Improvement';
        }

        return 'Poor';
    }
}
