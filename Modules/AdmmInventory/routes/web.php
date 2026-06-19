<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmInventory\Http\Controllers\AdmmInventoryController;
use Modules\AdmmInventory\Http\Controllers\SimCardController;
use Modules\AdmmInventory\Http\Controllers\ClientController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admminventories', AdmmInventoryController::class)->names('admminventory');
});


Route::middleware(['auth'])->prefix('admin/admm')->name('admin.admm.')->group(function () {
     // SIM Cards
    Route::prefix('sim-cards')->name('sim-cards.')->group(function () {
        Route::get('/',          [SimCardController::class, 'index'])->name('index');
        Route::get('/create',    [SimCardController::class, 'create'])->name('create');
        Route::get('/{simCard}/edit', [SimCardController::class, 'edit'])->name('edit');
    });
    // Clients
Route::prefix('clients')->name('clients.')->group(function () {
    Route::get('/',               [ClientController::class, 'index'])->name('index');
    Route::get('/create',         [ClientController::class, 'create'])->name('create');
    Route::get('/{client}/edit',  [ClientController::class, 'edit'])->name('edit');
});

});