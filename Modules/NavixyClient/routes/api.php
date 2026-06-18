<?php

use Illuminate\Support\Facades\Route;
use Modules\NavixyClient\Http\Controllers\NavixyClientController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('navixyclients', NavixyClientController::class)->names('navixyclient');
});
