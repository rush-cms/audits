<?php

declare(strict_types=1);

use App\Data\AuditData;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Support\Facades\Route;

Route::get('/preview/audit', function () {
    $audit = new AuditData(
        targetUrl: new Url('https://www.example.com/'),
        score: new AuditScore(0.87),
        lcp: MetricValue::fromDisplayValue('1.8 s'),
        fcp: MetricValue::fromDisplayValue('0.9 s'),
        cls: MetricValue::fromDisplayValue('0.025'),
        auditId: 'preview-' . now()->timestamp,
    );

    return view('reports.audit-preview', ['audit' => $audit]);
});
