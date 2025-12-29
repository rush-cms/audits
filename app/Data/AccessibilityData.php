<?php

declare(strict_types=1);

namespace App\Data;

use App\ValueObjects\AuditScore;
use Spatie\LaravelData\Data;

final class AccessibilityData extends Data
{
    /**
     * @param  array<int, array{id: string, title: string, description: string}>  $failedAudits
     */
    public function __construct(
        public readonly AuditScore $score,
        public readonly array $failedAudits = [],
    ) {}

    /**
     * @param  array<string, mixed>  $lighthouseResult
     */
    public static function fromLighthouseResult(array $lighthouseResult): self
    {
        $categories = $lighthouseResult['categories'] ?? [];
        $accessibility = $categories['accessibility'] ?? null;

        if (! $accessibility) {
            return new self(
                score: new AuditScore(0),
                failedAudits: [],
            );
        }

        $audits = $lighthouseResult['audits'] ?? [];
        $failedAudits = [];

        foreach ($accessibility['auditRefs'] ?? [] as $ref) {
            $id = $ref['id'];
            $audit = $audits[$id] ?? null;

            if ($audit && isset($audit['score']) && $audit['score'] < 1) {
                $failedAudits[] = [
                    'id' => $id,
                    'title' => $audit['title'] ?? '',
                    'description' => $audit['description'] ?? '',
                ];
            }
        }

        return new self(
            score: new AuditScore((float) $accessibility['score']),
            failedAudits: $failedAudits,
        );
    }
}
