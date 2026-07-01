<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDocuments\Http\Controllers\SimImportController;

Route::middleware('auth')
    ->prefix('admin/admm')
    ->name('admin.admm.')
    ->group(function () {

        Route::get('/sim-import', [SimImportController::class, 'index'])
            ->name('sim-import.index');

        Route::get('/gps-import', [SimImportController::class, 'gpsImportIndex'])
            ->name('gps-import.index');

        Route::get('/gps-import/template', [SimImportController::class, 'gpsTemplate'])
            ->name('gps-import.template');

        Route::get('/asset-register/import', function () {
            return view('admmdocuments::asset-import');
        })->name('asset-register.import');

    });