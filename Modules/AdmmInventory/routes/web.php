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

        // Technicians and CSV imports (built)
    Route::get('/technicians', fn () => view('admminventory::technicians.index'))->name('technicians.index');
    Route::get('/inventory-import', fn () => view('admminventory::imports.index'))->name('inventory-import.index');

    // Placeholders: each is replaced by the real screen when its step is built
        $soon = fn (string $title, string $step) => fn () => view('admminventory::placeholders.coming-soon', ['title' => $title, 'step' => $step]);

    Route::get('/pairing',           $soon('Hardware Pairing', 'B'))->name('pairing.index');
    Route::get('/technician-stock',  $soon('Technician Stock', 'C'))->name('technician-stock.index');
    Route::get('/unpaired-sims',     $soon('Unpaired SIMs', 'C'))->name('unpaired-sims.index');
    Route::get('/available-devices', $soon('Available Devices', 'C'))->name('available-devices.index');
    Route::get('/faulty-stock',      $soon('Faulty Devices & SIMs', 'C'))->name('faulty-stock.index');
    Route::get('/installations/new', $soon('Log Installation', 'D'))->name('installations.create');
    Route::get('/installations',     $soon('Installation Lookup', 'D'))->name('installations.index');
    Route::get('/vehicle-notes',     $soon('Vehicle Notes', 'E'))->name('vehicle-notes.index');
    
    });

    