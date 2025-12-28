<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuditController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/scan', [AuditController::class, 'store']);
});
