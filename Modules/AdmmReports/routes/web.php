<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmReports\Http\Controllers\AdmmReportsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admmreports', AdmmReportsController::class)->names('admmreports');
});
