<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\AuditData;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateAuditPdfJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditController extends Controller
{
    private const array ALLOWED_LOCALES = ['en', 'pt_BR', 'es'];

    public function store(Request $request): JsonResponse
    {
        /** @var array<mixed> $payload */
        $payload = $request->all();

        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        $lang = $this->validateLocale($request->input('lang', 'en'));

        $auditData = AuditData::fromPageSpeedPayload($payload);

        GenerateAuditPdfJob::dispatch($auditData, $lang);

        return response()->json([
            'message' => 'Audit queued',
            'audit_id' => $auditData->auditId,
            'lang' => $lang,
        ], 202);
    }

    private function validateLocale(mixed $lang): string
    {
        if (is_string($lang) && in_array($lang, self::ALLOWED_LOCALES, true)) {
            return $lang;
        }

        return 'en';
    }
}
