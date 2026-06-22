<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;

// Routes publiques (authentification)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Statistiques
    Route::get('/stats', [StatsController::class, 'index']);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Catégories de produits
    Route::apiResource('product-categories', ProductCategoryController::class);

    // Produits
    Route::apiResource('products', ProductController::class);

    // Mouvements de stock
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::get('products/{product}/stock-movements', [StockMovementController::class, 'byProduct']);
    Route::post('stock-movements', [StockMovementController::class, 'store']);

    // Commandes
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{order}/validate', [OrderController::class, 'validate']);
    
    // Factures PDF
    Route::get('orders/{order}/invoice/download', [InvoiceController::class, 'download']);
    Route::get('orders/{order}/invoice/preview', [InvoiceController::class, 'preview']);
});
