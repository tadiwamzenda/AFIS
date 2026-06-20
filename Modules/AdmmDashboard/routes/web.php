<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDashboard\Http\Controllers\DashboardController;

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});