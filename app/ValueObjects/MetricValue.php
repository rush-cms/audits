<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class MetricValue
{
    public function __construct(
        private float $value,
        private ?string $unit = null,
    ) {}

    public static function fromDisplayValue(string $displayValue): self
    {
        $displayValue = str_replace("\xC2\xA0", ' ', $displayValue);
        $displayValue = trim($displayValue);

        if (preg_match('/^([\d.]+)\s+(s|ms)$/i', $displayValue, $matches)) {
            $numericValue = (float) $matches[1];
            $unit = strtolower($matches[2]);

            if ($unit === 's') {
                return new self($numericValue * 1000, 'ms');
            }

            return new self($numericValue, 'ms');
        }

        if (preg_match('/^([\d.]+)(ms)$/i', $displayValue, $matches)) {
            return new self((float) $matches[1], 'ms');
        }

        return new self((float) $displayValue, null);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function toMilliseconds(): float
    {
        return $this->value;
    }

    public function toSeconds(): float
    {
        if ($this->unit === 'ms') {
            return $this->value / 1000;
        }

        return $this->value;
    }

    public function format(): string
    {
        if ($this->unit === 'ms') {
            if ($this->value >= 100) {
                return number_format($this->value / 1000, 1).' s';
            }

            return number_format($this->value, 0).' ms';
        }

        if ($this->value < 1) {
            return number_format($this->value, 3);
        }

        return number_format($this->value, 1);
    }
}
