<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmInventory\Http\Controllers\AdmmInventoryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admminventories', AdmmInventoryController::class)->names('admminventory');
});
