<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\AuditData;
use App\Http\Controllers\Controller;
use App\Jobs\FetchPageSpeedJob;
use App\Jobs\GenerateAuditPdfJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AuditController extends Controller
{
    private const array ALLOWED_LOCALES = ['en', 'pt_BR', 'es'];

    public function store(Request $request): JsonResponse
    {
        $lang = $this->validateLocale($request->input('lang', 'en'));

        if ($request->has('url')) {
            return $this->handleUrlRequest($request, $lang);
        }

        return $this->handlePayloadRequest($request, $lang);
    }

    private function handleUrlRequest(Request $request, string $lang): JsonResponse
    {
        $url = $request->input('url');

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 422);
        }

        $strategy = $request->input('strategy', 'mobile');
        if (! in_array($strategy, ['mobile', 'desktop'], true)) {
            $strategy = 'mobile';
        }

        $auditId = (string) Str::uuid();

        FetchPageSpeedJob::dispatch($url, $lang, $strategy);

        return response()->json([
            'message' => 'Audit queued',
            'audit_id' => $auditId,
            'lang' => $lang,
            'strategy' => $strategy,
        ], 202);
    }

    private function handlePayloadRequest(Request $request, string $lang): JsonResponse
    {
        /** @var array<mixed> $payload */
        $payload = $request->all();

        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        $lighthouseResult = $payload['lighthouseResult'] ?? $payload;

        $auditData = AuditData::fromLighthouseResult($lighthouseResult);

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
