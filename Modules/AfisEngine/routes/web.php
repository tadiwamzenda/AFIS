<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisEngine\Http\Controllers\AfisEngineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('afisengines', AfisEngineController::class)->names('afisengine');
});
