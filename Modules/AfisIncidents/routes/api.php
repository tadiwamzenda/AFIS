<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIncidents\Http\Controllers\AfisIncidentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('afisincidents', AfisIncidentsController::class)->names('afisincidents');
});
