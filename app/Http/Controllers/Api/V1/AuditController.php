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
    public function store(Request $request): JsonResponse
    {
        /** @var array<mixed> $payload */
        $payload = $request->all();

        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        $auditData = AuditData::fromPageSpeedPayload($payload);

        GenerateAuditPdfJob::dispatch($auditData);

        return response()->json([
            'message' => 'Audit queued',
            'audit_id' => $auditData->auditId,
        ], 202);
    }
}
