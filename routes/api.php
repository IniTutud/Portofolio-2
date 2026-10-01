<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PortfolioController;
use Illuminate\Support\Facades\Route;

Route::get('/portfolio', [PortfolioController::class, 'show']);
Route::middleware('web')->group(function () {
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    Route::middleware('auth')->group(function () {
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
        Route::post('/admin/upload', [PortfolioController::class, 'upload']);
        Route::put('/admin/portfolio', [PortfolioController::class, 'update']);
    });
});
