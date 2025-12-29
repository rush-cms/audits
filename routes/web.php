<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\PreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/preview/audit', PreviewController::class);
