<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class PreviewParametersData extends Data
{
    public function __construct(
        public readonly string $lang = 'en',
        public readonly float $score = 0.0,
        public readonly string $lcp = '',
        public readonly string $fcp = '',
        public readonly string $cls = '',
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromRequest(array $query): self
    {
        return new self(
            lang: self::parseLocale($query['lang'] ?? null),
            score: self::parseScore($query['score'] ?? null),
            lcp: self::parseMetric($query['lcp'] ?? null, 0.5, 4.5),
            fcp: self::parseMetric($query['fcp'] ?? null, 0.3, 3.5),
            cls: self::parseCls($query['cls'] ?? null),
        );
    }

    private static function parseLocale(mixed $value): string
    {
        $allowed = ['en', 'pt_BR', 'es'];

        if (is_string($value) && in_array($value, $allowed, true)) {
            return $value;
        }

        return 'en';
    }

    private static function parseScore(mixed $value): float
    {
        if (is_string($value) || is_numeric($value)) {
            $score = (float) $value;
            if ($score >= 0.0 && $score <= 1.0) {
                return $score;
            }
            if ($score >= 0 && $score <= 100) {
                return $score / 100;
            }
        }

        return rand(45, 99) / 100;
    }

    private static function parseMetric(mixed $value, float $min, float $max): string
    {
        if (is_string($value) && preg_match('/^(\d+\.?\d*)\s*(s|ms)?$/i', trim($value), $matches)) {
            $num = (float) $matches[1];
            $inputUnit = strtolower($matches[2] ?? 's');

            if ($inputUnit === 'ms') {
                $num = $num / 1000;
            }

            if ($num >= 0.1 && $num <= 10.0) {
                return number_format($num, 1).' s';
            }
        }

        $random = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);

        return number_format($random, 1).' s';
    }

    private static function parseCls(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            $cls = (float) $value;
            if ($cls >= 0.0 && $cls <= 1.0) {
                return number_format($cls, 3);
            }
        }

        $random = mt_rand(0, 300) / 1000;

        return number_format($random, 3);
    }
}
