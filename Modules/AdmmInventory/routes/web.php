<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmInventory\Http\Controllers\AdmmInventoryController;
use Modules\AdmmInventory\Http\Controllers\SimCardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admminventories', AdmmInventoryController::class)->names('admminventory');
});

 // SIM Cards
Route::middleware(['auth'])->prefix('admin/admm')->name('admin.admm.')->group(function () {
    Route::prefix('sim-cards')->name('sim-cards.')->group(function () {
        Route::get('/',          [SimCardController::class, 'index'])->name('index');
        Route::get('/create',    [SimCardController::class, 'create'])->name('create');
        Route::get('/{simCard}/edit', [SimCardController::class, 'edit'])->name('edit');
    });

});