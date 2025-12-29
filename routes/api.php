<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Middleware\ThrottleApiRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', ThrottleApiRequests::class])->group(function (): void {
    Route::post('/scan', [AuditController::class, 'store']);
    Route::get('/audits/{audit}', [AuditController::class, 'show']);
    Route::get('/stats', [StatsController::class, 'index']);
});
