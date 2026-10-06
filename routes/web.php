<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::view('/admin', 'welcome');
Route::post('/contact', ContactController::class)
    ->middleware('throttle:5,1')
    ->name('contact.store');
