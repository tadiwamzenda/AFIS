<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmReports\Http\Controllers\AdmmReportsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admmreports', AdmmReportsController::class)->names('admmreports');
});
