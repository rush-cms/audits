<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrFindAuditAction;
use App\Actions\IncrementScanCountAction;
use App\Http\Controllers\Controller;
use App\Jobs\FetchPageSpeedJob;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditController extends Controller
{
    private const array ALLOWED_LOCALES = ['en', 'pt_BR', 'es'];

    private const array ALLOWED_STRATEGIES = ['mobile', 'desktop'];

    public function store(
        Request $request,
        IncrementScanCountAction $countAction,
        CreateOrFindAuditAction $createOrFindAudit,
    ): JsonResponse {
        $url = $request->input('url');

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 422);
        }

        $lang = $this->validateLocale($request->input('lang', 'en'));
        $strategy = $this->validateStrategy($request->input('strategy', 'mobile'));

        $audit = $createOrFindAudit->execute($url, $strategy, $lang);

        if ($audit->wasRecentlyCreated) {
            $countAction->execute();
            FetchPageSpeedJob::dispatch($audit->id, $url, $strategy, $lang);
        }

        return response()->json([
            'message' => 'Audit queued',
            'audit_id' => $audit->id,
            'url' => $audit->url,
            'lang' => $audit->lang,
            'strategy' => $audit->strategy,
            'status' => $audit->status,
        ], 202);
    }

    public function show(Audit $audit): JsonResponse
    {
        return response()->json([
            'id' => $audit->id,
            'url' => $audit->url,
            'strategy' => $audit->strategy,
            'lang' => $audit->lang,
            'status' => $audit->status,
            'score' => $audit->score,
            'metrics' => $audit->metrics,
            'pdf_url' => $audit->pdf_url,
            'error_message' => $audit->error_message,
            'created_at' => $audit->created_at?->toIso8601String(),
            'completed_at' => $audit->completed_at?->toIso8601String(),
        ]);
    }

    private function validateLocale(mixed $lang): string
    {
        if (is_string($lang) && in_array($lang, self::ALLOWED_LOCALES, true)) {
            return $lang;
        }

        return 'en';
    }

    private function validateStrategy(mixed $strategy): string
    {
        if (is_string($strategy) && in_array($strategy, self::ALLOWED_STRATEGIES, true)) {
            return $strategy;
        }

        return 'mobile';
    }
}
