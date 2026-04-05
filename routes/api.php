<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanPriceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionTransactionController;

Route::get('plans/public', [PlanController::class, 'publicIndex']);

Route::middleware('throttle:5,1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login',    [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('plans', PlanController::class)->only(['index']);
    Route::get('plans/{plan}/details', [PlanController::class, 'userShow']);

    Route::middleware('admin')->group(function () {
        Route::get('plans/{plan}',            [PlanController::class, 'show']);
        Route::post('plans',                  [PlanController::class, 'store']);
        Route::put('plans/{plan}',            [PlanController::class, 'update']);
        Route::patch('plans/{plan}',          [PlanController::class, 'patch']);
        Route::delete('plans/{plan}',         [PlanController::class, 'destroy']);
    });

    Route::apiResource('plan-prices', PlanPriceController::class)->only(['index', 'show']);

    Route::middleware('admin')->group(function () {
        Route::post('plan-prices',                      [PlanPriceController::class, 'store']);
        Route::put('plan-prices/{planPrice}',           [PlanPriceController::class, 'update']);
        Route::patch('plan-prices/{planPrice}',         [PlanPriceController::class, 'patch']);
        Route::delete('plan-prices/{planPrice}',        [PlanPriceController::class, 'destroy']);
    });

    Route::apiResource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('subscriptions/{subscription}/cancel',        [SubscriptionController::class, 'cancel']);
    Route::post('subscriptions/{subscription}/renew',         [SubscriptionController::class, 'renew']);
    Route::post('subscriptions/{subscription}/mark-past-due', [SubscriptionController::class, 'markPastDue']);
    Route::post('subscriptions/{subscription}/activate',      [SubscriptionController::class, 'activate']);
    Route::post('subscriptions/{subscription}/expire',        [SubscriptionController::class, 'expire']);

    Route::apiResource('subscription-transactions', SubscriptionTransactionController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('subscription-transactions/{transaction}/mark-paid',   [SubscriptionTransactionController::class, 'markPaid']);
    Route::post('subscription-transactions/{transaction}/mark-failed', [SubscriptionTransactionController::class, 'markFailed']);
    Route::post('subscription-transactions/{transaction}/refund',      [SubscriptionTransactionController::class, 'refund']);

});
