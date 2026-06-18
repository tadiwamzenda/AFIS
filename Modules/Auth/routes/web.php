<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::get('/', fn() => redirect()->route('login'));
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Temporary placeholders — replaced when portal modules are built
    Route::get('/admin/dashboard', fn() => 'Admin dashboard — coming soon')->name('admin.dashboard');
    Route::get('/client/dashboard', fn() => 'Client dashboard — coming soon')->name('client.dashboard');
});