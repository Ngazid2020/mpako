<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Commerce\CreditController;
use App\Http\Controllers\Api\Commerce\CustomerController;
use App\Http\Controllers\Api\Commerce\ProductController;
use App\Http\Controllers\Api\Commerce\SaleController;
use App\Http\Controllers\Api\Commerce\SyncController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Middleware\Api\ResolveShop;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────
// Routes publiques (sans token)
// ─────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// ─────────────────────────────────────────────
// Routes protégées (token Sanctum requis)
// ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // Push notifications
    Route::post('push-subscriptions',   [PushSubscriptionController::class, 'store']);
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy']);

    // ─────────────────────────────────────────
    // Commerce — scoped par shop
    // ─────────────────────────────────────────
    Route::prefix('shop/{shop}')
        ->middleware(ResolveShop::class)
        ->group(function () {

            // Sync offline (pull & push)
            Route::get('pull',  [SyncController::class, 'pull']);
            Route::post('push', [SyncController::class, 'push']);

            // Produits
            Route::get('products', [ProductController::class, 'index']);

            // Clients
            Route::get('customers',                      [CustomerController::class, 'index']);
            Route::get('customers/{customer}/credits',   [CustomerController::class, 'credits']);

            // Crédits
            Route::get('credits',             [CreditController::class, 'index']);
            Route::post('credits/{credit}/pay', [CreditController::class, 'pay']);

            // Ventes
            Route::get('sales',  [SaleController::class, 'index']);
            Route::post('sales', [SaleController::class, 'store']);
        });
});
