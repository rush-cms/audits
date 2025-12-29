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
use Spatie\LaravelData\Exceptions\ValidationException;

final class AuditController extends Controller
{
    public function store(
        Request $request,
        IncrementScanCountAction $countAction,
        CreateOrFindAuditAction $createOrFindAudit,
    ): JsonResponse {
        try {
            $data = ScanData::validateAndCreate([
                'url' => $request->input('url'),
                'lang' => $request->input('lang', 'en'),
                'strategy' => $request->input('strategy', 'mobile'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
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

        if ($audit->wasRecentlyCreated) {
            $countAction->execute();
            FetchPageSpeedJob::dispatch(
                $audit->id,
                $data->url->getValue(),
                $data->strategy->value,
                $data->lang->value
            );
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
