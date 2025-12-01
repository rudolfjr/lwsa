<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/inventory', [InventoryController::class, 'store']);
        Route::get('/inventory', [InventoryController::class, 'index']);

        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{id}', [SaleController::class, 'show']);

        Route::get('/reports/sales', [ReportController::class, 'sales']);
    });
});
