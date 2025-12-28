<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();

        return response()->json([
            'minute' => (int) Cache::get('scans:minute:'.$now->format('Y-m-d-H-i'), 0),
            'hour' => (int) Cache::get('scans:hour:'.$now->format('Y-m-d-H'), 0),
            'day' => (int) Cache::get('scans:day:'.$now->format('Y-m-d'), 0),
            'month' => (int) Cache::get('scans:month:'.$now->format('Y-m'), 0),
        ]);
    }
}
