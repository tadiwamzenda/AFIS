<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmInventory\Http\Controllers\AdmmInventoryController;
use Modules\AdmmInventory\Http\Controllers\ClientController;
use Modules\AdmmReports\Http\Controllers\ReportController as AdmmReportController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admminventories', AdmmInventoryController::class)->names('admminventory');
});


Route::middleware(['auth'])->prefix('admin/admm')->name('admin.admm.')->group(function () {
    // Audit Log
    Route::get('/audit-log', [AdmmReportController::class, 'auditTrail'])->name('audit-log');
    // Clients
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/',               [ClientController::class, 'index'])->name('index');
        Route::get('/create',         [ClientController::class, 'create'])->name('create');
        Route::get('/{client}/edit',  [ClientController::class, 'edit'])->name('edit');
    });
    //Asset Inventory
    Route::get('/asset-register', function () {
        return view('admminventory::asset-register.index');
    })->name('asset-register.index');
    //Stock-management
    Route::get('/asset-stock', function () {
    return view('admminventory::stock-management.index');
    })->name('asset-stock.index');
    // Asset Register PDF export
    Route::get('/asset-register/export-pdf', [\Modules\AdmmInventory\Http\Controllers\AssetRegisterController::class, 'exportPdf'])
    ->name('asset-register.export-pdf');

    Route::get('/asset-stock/export-pdf', [\Modules\AdmmInventory\Http\Controllers\StockManagementController::class, 'exportPdf'])
    ->name('asset-stock.export-pdf');
    
    });

    