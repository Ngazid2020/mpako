<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Commerce\CreditController;
use App\Http\Controllers\Api\Commerce\CustomerController;
use App\Http\Controllers\Api\Commerce\ExpenseController;
use App\Http\Controllers\Api\Commerce\ProductController;
use App\Http\Controllers\Api\Commerce\PurchaseController;
use App\Http\Controllers\Api\Commerce\ReportController;
use App\Http\Controllers\Api\Commerce\SaleController;
use App\Http\Controllers\Api\Commerce\StockController;
use App\Http\Controllers\Api\Commerce\SupplierController;
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
            Route::get('products',  [ProductController::class, 'index']);
            Route::post('products', [ProductController::class, 'store']);

            // Clients
            Route::get('customers',                      [CustomerController::class, 'index']);
            Route::post('customers',                     [CustomerController::class, 'store']);
            Route::get('customers/{customer}/credits',   [CustomerController::class, 'credits']);

            // Crédits
            Route::get('credits',             [CreditController::class, 'index']);
            Route::post('credits/{credit}/pay', [CreditController::class, 'pay']);

            // Ventes
            Route::get('sales',  [SaleController::class, 'index']);
            Route::post('sales', [SaleController::class, 'store']);

            // Fournisseurs
            Route::get('suppliers', [SupplierController::class, 'index']);
            Route::post('suppliers/{supplier}/pay', [SupplierController::class, 'pay']);

            // Achats
            Route::get('purchases',                          [PurchaseController::class, 'index']);
            Route::post('purchases',                         [PurchaseController::class, 'store']);
            Route::post('purchases/{purchase}/complete',     [PurchaseController::class, 'complete']);
            Route::post('purchases/{purchase}/pay',          [PurchaseController::class, 'pay']);

            // Dépenses
            Route::get('expenses', [ExpenseController::class, 'index']);
            Route::post('expenses', [ExpenseController::class, 'store']);

            // Mouvements de stock
            Route::get('stock-movements', [StockController::class, 'movements']);
            Route::post('stock-adjustments', [StockController::class, 'adjust']);

            // Rapports
            Route::get('reports/summary', [ReportController::class, 'summary']);
            Route::get('reports/sales-by-day', [ReportController::class, 'salesByDay']);
            Route::get('reports/top-products', [ReportController::class, 'topProducts']);
            Route::get('reports/expenses-by-category', [ReportController::class, 'expensesByCategory']);
        });
});
