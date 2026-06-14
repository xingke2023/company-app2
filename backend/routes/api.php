<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\AssistantNoteController;
use App\Http\Controllers\Api\QuickActionController;
use App\Http\Controllers\Api\SalesOrderController;
use Illuminate\Support\Facades\Route;

// 公开接口（小程序免登录拉取菜单/标题/AI对话；单租户，无需认证）
Route::get('/quick-actions', [QuickActionController::class, 'index']);
Route::get('/app-config', [AppConfigController::class, 'index']);
Route::post('/ai/message', [AiAssistantController::class, 'message']);
Route::post('/ai/voice', [AiAssistantController::class, 'voice']);
Route::get('/assistant/notes', [AssistantNoteController::class, 'index']);
Route::post('/assistant/notes', [AssistantNoteController::class, 'store']);
Route::delete('/assistant/notes/{note}', [AssistantNoteController::class, 'destroy']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.hybrid')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // API Key 管理（永久有效，供外部服务账号使用）
        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiKeyController::class, 'store']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
    });
});

Route::middleware('auth.hybrid')->group(function () {
    // 进销存
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    Route::get('/sales-orders', [SalesOrderController::class, 'index']);
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show']);

    // 采购订单（只读）
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);

    // CRM
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('leads', LeadController::class);
    Route::apiResource('opportunities', OpportunityController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('activities', ActivityController::class);
});
