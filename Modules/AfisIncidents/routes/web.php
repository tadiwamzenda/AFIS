<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIncidents\Http\Controllers\IncidentController;

// Admin routes
Route::middleware('auth')->prefix('admin/afis')->name('admin.afis.')->group(function () {
    Route::get('/incidents',                     [IncidentController::class, 'adminIndex'])->name('incidents.index');
    Route::get('/incidents/{clientId}/log',      [IncidentController::class, 'adminLog'])->name('incidents.log');
    Route::get('/incidents/{clientId}/archive',  [IncidentController::class, 'adminArchive'])->name('incidents.archive');
    Route::get('/incidents/{incident}/download', [IncidentController::class, 'adminDownload'])->name('incidents.download');
});

// Client portal routes
Route::middleware('auth')->prefix('client/incidents')->name('client.incidents.')->group(function () {
    Route::get('/log',     [IncidentController::class, 'clientLog'])->name('log');
    Route::get('/archive', [IncidentController::class, 'clientArchive'])->name('archive');
});