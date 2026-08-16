<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIntelligence\Http\Controllers\IntelligenceController;

// Admin routes
Route::middleware('auth')->prefix('admin/afis/intelligence')->name('admin.afis.intelligence.')->group(function () {
    Route::get('/',                  [IntelligenceController::class, 'index'])->name('index');
    Route::get('/{clientId}',        [IntelligenceController::class, 'dashboard'])->name('dashboard');

});

// Client portal routes
Route::middleware('auth')->prefix('client/intelligence')->name('client.intelligence.')->group(function () {
    Route::get('/',        [IntelligenceController::class, 'clientDashboard'])->name('dashboard');
    Route::get('/archive', [IntelligenceController::class, 'clientArchive'])->name('archive');
});