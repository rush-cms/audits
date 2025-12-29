<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateOrFindAuditAction;
use App\Actions\IncrementScanCountAction;
use App\Data\ScanData;
use App\Http\Controllers\Controller;
use App\Jobs\FetchPageSpeedJob;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AuditController extends Controller
{
    public function store(
        Request $request,
        IncrementScanCountAction $countAction,
        CreateOrFindAuditAction $createOrFindAudit,
    ): JsonResponse {
        $token = $request->user()?->currentAccessToken();

        Log::channel('audits')->info('API request received', [
            'endpoint' => '/api/v1/scan',
            'token_id' => $token?->id,
            'token_name' => $token?->name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $request->only(['url', 'lang', 'strategy']),
        ]);

        try {
            $data = ScanData::validateAndCreate([
                'url' => $request->input('url'),
                'lang' => $request->input('lang', 'en'),
                'strategy' => $request->input('strategy', 'mobile'),
            ]);
        } catch (\Exception $e) {
            Log::channel('audits')->warning('Validation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'payload' => $request->only(['url', 'lang', 'strategy']),
            ]);

            return response()->json([
                'message' => 'Invalid request',
                'error' => $e->getMessage(),
            ], 422);
        }

        $audit = $createOrFindAudit->execute(
            $data->url->getValue(),
            $data->strategy->value,
            $data->lang->value
        );

        $audit->update([
            'created_by_token_id' => $token?->id,
            'created_by_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($audit->wasRecentlyCreated) {
            Log::channel('audits')->info('New audit created', [
                'audit_id' => $audit->id,
                'url' => $audit->url,
                'strategy' => $audit->strategy,
                'lang' => $audit->lang,
            ]);

            $countAction->execute();
            FetchPageSpeedJob::dispatch(
                $audit->id,
                $data->url->getValue(),
                $data->strategy->value,
                $data->lang->value
            );

            Log::channel('audits')->info('Job dispatched', [
                'audit_id' => $audit->id,
                'job' => 'FetchPageSpeedJob',
            ]);
        } else {
            Log::channel('audits')->info('Existing audit returned', [
                'audit_id' => $audit->id,
                'status' => $audit->status,
                'reason' => 'idempotency_check',
            ]);
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
            'created_at' => $audit->created_at->toIso8601String(),
            'completed_at' => $audit->completed_at?->toIso8601String(),
        ]);
    }
}
