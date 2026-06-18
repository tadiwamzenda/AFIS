<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmInventory\Http\Controllers\AdmmInventoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admminventories', AdmmInventoryController::class)->names('admminventory');
});
