<?php

use Illuminate\Support\Facades\Route;
use Modules\ExportEngine\Http\Controllers\ExportEngineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('exportengines', ExportEngineController::class)->names('exportengine');
});
