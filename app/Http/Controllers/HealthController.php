<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = $this->healthCheckService->check();

        $httpStatus = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $httpStatus);
    }
}
