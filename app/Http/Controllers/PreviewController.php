<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\AccessibilityData;
use App\Data\AuditData;
use App\Data\SeoData;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PreviewController extends Controller
{
    private const array ALLOWED_LOCALES = ['en', 'pt_BR', 'es'];

    public function __invoke(Request $request): View
    {
        $lang = $this->parseLocale($request->query('lang'));
        app()->setLocale($lang);

        $score = $this->parseScore($request->query('score'));
        $lcp = $this->parseMetric($request->query('lcp'), 0.5, 4.5, 's');
        $fcp = $this->parseMetric($request->query('fcp'), 0.3, 3.5, 's');
        $cls = $this->parseCls($request->query('cls'));

        $audit = $this->buildAuditData($score, $lcp, $fcp, $cls);

        return view('reports.audit-preview', [
            'audit' => $audit,
            'currentLang' => $lang,
        ]);
    }

    private function parseLocale(mixed $value): string
    {
        if (is_string($value) && in_array($value, self::ALLOWED_LOCALES, true)) {
            return $value;
        }

        return 'en';
    }

    private function parseScore(mixed $value): float
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

    private function parseMetric(mixed $value, float $min, float $max, string $unit): string
    {
        if (is_string($value) && preg_match('/^(\d+\.?\d*)\s*(s|ms)?$/i', trim($value), $matches)) {
            $num = (float) $matches[1];
            $inputUnit = strtolower($matches[2] ?? 's');

            if ($inputUnit === 'ms') {
                $num = $num / 1000;
            }

            if ($num >= 0.1 && $num <= 10.0) {
                return number_format($num, 1).' '.$unit;
            }
        }

        $random = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);

        return number_format($random, 1).' '.$unit;
    }

    private function parseCls(mixed $value): string
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

    private function buildAuditData(float $score, string $lcp, string $fcp, string $cls): AuditData
    {
        $seoScore = rand(70, 100) / 100;
        $accessibilityScore = rand(75, 100) / 100;

        return new AuditData(
            targetUrl: new Url('https://www.example.com/'),
            score: new AuditScore($score),
            lcp: MetricValue::fromDisplayValue($lcp),
            fcp: MetricValue::fromDisplayValue($fcp),
            cls: MetricValue::fromDisplayValue($cls),
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
