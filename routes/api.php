<?php

use App\Http\Controllers\Api\KPIController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RefundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store']);
    
    // Refunds
    Route::post('orders/{order}/refunds', [RefundController::class, 'store']);
    Route::get('refunds/{refund}', [RefundController::class, 'show']);
    
    // KPIs
    Route::get('kpis/daily', [KPIController::class, 'daily']);
    Route::get('kpis/leaderboard', [KPIController::class, 'leaderboard']);
});
