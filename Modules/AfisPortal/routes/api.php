<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPortal\Http\Controllers\AfisPortalController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('afisportals', AfisPortalController::class)->names('afisportal');
});
