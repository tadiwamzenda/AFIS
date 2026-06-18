<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDashboard\Http\Controllers\AdmmDashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admmdashboards', AdmmDashboardController::class)->names('admmdashboard');
});
