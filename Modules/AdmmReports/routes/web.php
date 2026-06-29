<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmReports\Http\Controllers\ReportController;

Route::middleware('auth')->prefix('admin/admm/reports')->name('admin.admm.reports.')->group(function () {
    Route::get('/',                    [ReportController::class, 'index'])->name('index');
    Route::get('/sim-inventory',       [ReportController::class, 'simInventory'])->name('sim-inventory');
    Route::get('/sim-by-provider',     [ReportController::class, 'simByProvider'])->name('sim-by-provider');
    Route::get('/sim-by-batch',        [ReportController::class, 'simByBatch'])->name('sim-by-batch');
    Route::get('/sim-by-status',       [ReportController::class, 'simByStatus'])->name('sim-by-status');
    Route::get('/device-inventory',    [ReportController::class, 'deviceInventory'])->name('device-inventory');
    Route::get('/devices-by-client',   [ReportController::class, 'devicesByClient'])->name('devices-by-client');
    Route::get('/devices-in-stock',    [ReportController::class, 'devicesInStock'])->name('devices-in-stock');
    Route::get('/accessory-inventory', [ReportController::class, 'accessoryInventory'])->name('accessory-inventory');
    Route::get('/accessories-by-type', [ReportController::class, 'accessoriesByType'])->name('accessories-by-type');
    Route::get('/assignment-chain',    [ReportController::class, 'assignmentChain'])->name('assignment-chain');
    Route::get('/internal-stock',      [ReportController::class, 'internalStock'])->name('internal-stock');
    Route::get('/audit-trail',         [ReportController::class, 'auditTrail'])->name('audit-trail');
    
    
});