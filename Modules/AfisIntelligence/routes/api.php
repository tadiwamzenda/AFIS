<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIntelligence\Http\Controllers\AfisIntelligenceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('afisintelligences', AfisIntelligenceController::class)->names('afisintelligence');
});
