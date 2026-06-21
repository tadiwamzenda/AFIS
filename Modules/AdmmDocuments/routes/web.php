<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDocuments\Http\Controllers\SimImportController;

Route::middleware('auth')
    ->prefix('admin/admm')
    ->name('admin.admm.')
    ->group(function () {

        Route::get('/sim-import', [SimImportController::class, 'index'])
            ->name('sim-import.index');

    });