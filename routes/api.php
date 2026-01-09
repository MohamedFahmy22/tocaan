<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });
});

/*
|--------------------------------------------------------------------------
| Protected API Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Order Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->group(function () {
        // List and CRUD operations
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('orders.my-orders');
        Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show')->where('id', '[0-9]+');
        Route::put('/{id}', [OrderController::class, 'update'])->name('orders.update')->where('id', '[0-9]+');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('orders.destroy')->where('id', '[0-9]+');

        // Status transitions
        Route::patch('/{id}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm')->where('id', '[0-9]+');
        Route::patch('/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel')->where('id', '[0-9]+');

        // Order payments
        Route::get('/{orderId}/payments', [PaymentController::class, 'orderPayments'])->name('orders.payments');
        Route::post('/{orderId}/payments', [PaymentController::class, 'processPayment'])->name('orders.payments.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Payment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/{id}', [PaymentController::class, 'show'])->name('payments.show')->where('id', '[0-9]+');
    });

    /*
    |--------------------------------------------------------------------------
    | Payment Methods Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/payment-methods', [PaymentController::class, 'paymentMethods'])->name('payment-methods.index');
});

/*
|--------------------------------------------------------------------------
| Health Check Route
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
})->name('api.health');
