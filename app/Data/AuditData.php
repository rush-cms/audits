<?php

declare(strict_types=1);

namespace App\Data;

use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class AuditData extends Data
{
    public function __construct(
        public readonly Url $targetUrl,
        public readonly AuditScore $score,
        public readonly MetricValue $lcp,
        public readonly MetricValue $fcp,
        public readonly MetricValue $cls,
        public readonly string $auditId,
    ) {}

    /**
     * Create from lighthouseResult object (the nested object, not full PageSpeed response).
     *
     * @param  array<string, mixed>  $lighthouseResult
     */
    public static function fromLighthouseResult(array $lighthouseResult): self
    {
        /** @var array<string, mixed> $audits */
        $audits = $lighthouseResult['audits'];

        /** @var array<string, mixed> $categories */
        $categories = $lighthouseResult['categories'];

        /** @var array<string, mixed> $performance */
        $performance = $categories['performance'];

        /** @var array<string, mixed> $lcpAudit */
        $lcpAudit = $audits['largest-contentful-paint'];

        /** @var array<string, mixed> $fcpAudit */
        $fcpAudit = $audits['first-contentful-paint'];

        /** @var array<string, mixed> $clsAudit */
        $clsAudit = $audits['cumulative-layout-shift'];

        return new self(
            targetUrl: new Url((string) $lighthouseResult['finalDisplayedUrl']),
            score: new AuditScore((float) $performance['score']),
            lcp: MetricValue::fromDisplayValue((string) $lcpAudit['displayValue']),
            fcp: MetricValue::fromDisplayValue((string) $fcpAudit['displayValue']),
            cls: MetricValue::fromDisplayValue((string) $clsAudit['displayValue']),
            auditId: (string) Str::uuid(),
        );
    }
}
