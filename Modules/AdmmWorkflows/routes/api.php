<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmWorkflows\Http\Controllers\AdmmWorkflowsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admmworkflows', AdmmWorkflowsController::class)->names('admmworkflows');
});
