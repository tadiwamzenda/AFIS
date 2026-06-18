<?php

use Illuminate\Support\Facades\Route;
use Modules\ExportEngine\Http\Controllers\ExportEngineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('exportengines', ExportEngineController::class)->names('exportengine');
});
