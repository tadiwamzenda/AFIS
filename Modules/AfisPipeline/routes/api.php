<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPipeline\Http\Controllers\AfisPipelineController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('afispipelines', AfisPipelineController::class)->names('afispipeline');
});
