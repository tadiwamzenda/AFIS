<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisEngine\Http\Controllers\AfisEngineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('afisengines', AfisEngineController::class)->names('afisengine');
});
