<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIncidents\Http\Controllers\AfisIncidentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('afisincidents', AfisIncidentsController::class)->names('afisincidents');
});
