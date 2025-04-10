<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->as('api.auth.')
    ->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:api')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        });
    });

Route::middleware('auth:api')
    ->name('api.')
    ->group(function (): void {
        Route::apiResource('/orders', OrderController::class)->except('destroy');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'destroy'])->name('orders.destroy');
    });
