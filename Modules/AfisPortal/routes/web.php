<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPortal\Http\Controllers\PortalController;
use Modules\AfisPortal\Http\Controllers\ReportController;
use Modules\AfisPortal\Http\Controllers\FleetStateReportController;

// Admin AFIS routes
Route::middleware('auth')->prefix('admin/afis')->name('admin.afis.')->group(function () {
    Route::get('/fleet',                    [PortalController::class, 'fleetOverview'])->name('fleet');
    Route::get('/fleet/{clientId}',         [PortalController::class, 'clientFleet'])->name('client-fleet');
    Route::get('/vehicle/{trackerId}',      [PortalController::class, 'vehicleInspector'])->name('vehicle');
    Route::get('/reports/{clientId}',       [PortalController::class, 'reports'])->name('reports');
    Route::get('/fleet/{clientId}/offline-report', [FleetStateReportController::class, 'offlineReport'])->name('fleet.offline-report');
});
// Client portal routes
Route::middleware('auth')->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard',                [PortalController::class, 'clientDashboard'])->name('dashboard');
    Route::get('/vehicle/{trackerId}',      [PortalController::class, 'clientVehicleInspector'])->name('vehicle');
    Route::get('/reports',                  [PortalController::class, 'clientReports'])->name('reports');
    Route::get('/reports/download/{id}',    [ReportController::class, 'download'])->name('reports.download');
});

// Report generation routes
Route::middleware('auth')->prefix('admin/afis/generate')->name('admin.afis.reports.')->group(function () {
    Route::get('/standard', [ReportController::class, 'standardReport'])->name('standard');
    Route::get('/ai',       [ReportController::class, 'aiReport'])->name('ai');
});

// Client portal report routes
Route::middleware('auth')->prefix('client/generate')->name('client.reports.generate.')->group(function () {
    Route::get('/standard', [ReportController::class, 'clientStandardReport'])->name('standard');
    Route::get('/ai',       [ReportController::class, 'clientAiReport'])->name('ai');
});

Route::get('/admin/afis/report-hub', function () {
    return view('afisportal::admin.report-hub');
})->name('admin.afis.report-hub')->middleware('auth');

Route::middleware('auth')->prefix('admin/afis')->name('admin.afis.')->group(function () {
    Route::get('/report-hub',                    fn() => view('afisportal::admin.report-hub'))->name('report-hub');
    Route::get('/generate/standard',             [ReportController::class, 'standardReport'])->name('reports.standard');
    Route::get('/generate/ai',                   [ReportController::class, 'aiReport'])->name('reports.ai');
    Route::get('/generate/download/{id}',        [ReportController::class, 'download'])->name('reports.download');
    Route::get('/vehicle-reports/{id}/download',  [ReportController::class, 'downloadVehicleReport'])->name('vehicle-reports.download');
    Route::delete('/generate/delete/{id}',       [ReportController::class, 'destroy'])->name('reports.destroy');
    
});