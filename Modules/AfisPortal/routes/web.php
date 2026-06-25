<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPortal\Http\Controllers\PortalController;

// Admin AFIS routes
Route::middleware('auth')->prefix('admin/afis')->name('admin.afis.')->group(function () {
    Route::get('/fleet',                    [PortalController::class, 'fleetOverview'])->name('fleet');
    Route::get('/fleet/{clientId}',         [PortalController::class, 'clientFleet'])->name('client-fleet');
    Route::get('/vehicle/{trackerId}',      [PortalController::class, 'vehicleInspector'])->name('vehicle');
    Route::get('/reports/{clientId}',       [PortalController::class, 'reports'])->name('reports');
});

// Client portal routes
Route::middleware('auth')->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard',                [PortalController::class, 'clientDashboard'])->name('dashboard');
    Route::get('/vehicle/{trackerId}',      [PortalController::class, 'clientVehicleInspector'])->name('vehicle');
    Route::get('/reports',                  [PortalController::class, 'clientReports'])->name('reports');
});