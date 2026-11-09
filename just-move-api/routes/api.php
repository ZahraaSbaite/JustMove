<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\AdminSettingsController;
use App\Http\Controllers\Api\AdminUploadController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// ── Storefront (public) ─────────────────────────────────────────────
Route::get('/settings', [StoreController::class, 'settings']);
Route::get('/categories', [StoreController::class, 'categories']);
Route::get('/products', [StoreController::class, 'products']);
Route::get('/products/{id}/bought-together', [StoreController::class, 'boughtTogether']);
Route::get('/products/{slug}', [StoreController::class, 'product']);

Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
Route::get('/orders/{id}', [OrderController::class, 'show']);

// ── Admin ───────────────────────────────────────────────────────────
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

Route::prefix('admin')->middleware(['auth:sanctum', EnsureAdmin::class])->group(function () {
    Route::get('/me', [AdminAuthController::class, 'me']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/stats', [AdminOrderController::class, 'stats']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::patch('/orders/{id}', [AdminOrderController::class, 'update']);

    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::get('/products/{id}', [AdminProductController::class, 'show']);
    Route::put('/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);

    Route::put('/settings', [AdminSettingsController::class, 'update']);
    Route::post('/uploads', [AdminUploadController::class, 'store']);
});
