<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDashboard\Http\Controllers\AdmmDashboardController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admmdashboards', AdmmDashboardController::class)->names('admmdashboard');
});
