<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AccessibilityData;
use App\Data\AuditData;
use App\Data\PreviewParametersData;
use App\Data\SeoData;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\SafeUrl;

final class BuildPreviewAuditAction
{
    public function execute(PreviewParametersData $params): AuditData
    {
        $seoScore = rand(70, 100) / 100;
        $accessibilityScore = rand(75, 100) / 100;

        return new AuditData(
            targetUrl: new SafeUrl('https://www.example.com/'),
            score: new AuditScore($params->score),
            lcp: MetricValue::fromDisplayValue($params->lcp),
            fcp: MetricValue::fromDisplayValue($params->fcp),
            cls: MetricValue::fromDisplayValue($params->cls),
            auditId: 'preview-'.now()->timestamp,
            seo: new SeoData(
                score: new AuditScore($seoScore),
                failedAudits: $seoScore < 0.9 ? [
                    ['id' => 'robots-txt', 'title' => 'robots.txt is not valid', 'description' => ''],
                ] : [],
            ),
            accessibility: new AccessibilityData(
                score: new AuditScore($accessibilityScore),
                failedAudits: $accessibilityScore < 0.95 ? [
                    ['id' => 'link-name', 'title' => 'Links do not have a discernible name', 'description' => ''],
                ] : [],
            ),
            desktopScreenshot: 'https://placehold.co/600x400/1e293b/94a3b8?text=Desktop+Preview',
            mobileScreenshot: 'https://placehold.co/300x600/1e293b/94a3b8?text=Mobile',
        );
    }
}
