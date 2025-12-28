<?php

declare(strict_types=1);

use App\Data\AuditData;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/preview/audit', function (Request $request) {
    $allowedLocales = ['en', 'pt_BR', 'es'];

    $lang = $request->query('lang');
    if (! is_string($lang) || ! in_array($lang, $allowedLocales, true)) {
        $lang = 'en';
    }

    app()->setLocale($lang);

    $score = parseScore($request->query('score'));
    $lcp = parseMetric($request->query('lcp'), 0.5, 4.5, 's');
    $fcp = parseMetric($request->query('fcp'), 0.3, 3.5, 's');
    $cls = parseCls($request->query('cls'));

    $audit = new AuditData(
        targetUrl: new Url('https://www.example.com/'),
        score: new AuditScore($score),
        lcp: MetricValue::fromDisplayValue($lcp),
        fcp: MetricValue::fromDisplayValue($fcp),
        cls: MetricValue::fromDisplayValue($cls),
        auditId: 'preview-'.now()->timestamp,
    );

    return view('reports.audit-preview', [
        'audit' => $audit,
        'currentLang' => $lang,
    ]);
});

function parseScore(mixed $value): float
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

function parseMetric(mixed $value, float $min, float $max, string $unit): string
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

function parseCls(mixed $value): string
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
