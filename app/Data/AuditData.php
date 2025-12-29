<?php

declare(strict_types=1);

namespace App\Data;

use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\SafeUrl;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class AuditData extends Data
{
    public function __construct(
        public readonly SafeUrl $targetUrl,
        public readonly AuditScore $score,
        public readonly MetricValue $lcp,
        public readonly MetricValue $fcp,
        public readonly MetricValue $cls,
        public readonly string $auditId,
        public readonly ?SeoData $seo = null,
        public readonly ?AccessibilityData $accessibility = null,
        public readonly ?string $desktopScreenshot = null,
        public readonly ?string $mobileScreenshot = null,
        public readonly bool $screenshotFailed = false,
        public readonly ?string $screenshotError = null,
    ) {}

    /**
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
            targetUrl: new SafeUrl((string) $lighthouseResult['finalDisplayedUrl']),
            score: new AuditScore((float) $performance['score']),
            lcp: MetricValue::fromDisplayValue((string) $lcpAudit['displayValue']),
            fcp: MetricValue::fromDisplayValue((string) $fcpAudit['displayValue']),
            cls: MetricValue::fromDisplayValue((string) $clsAudit['displayValue']),
            auditId: (string) Str::uuid(),
            seo: SeoData::fromLighthouseResult($lighthouseResult),
            accessibility: AccessibilityData::fromLighthouseResult($lighthouseResult),
        );
    }
}
