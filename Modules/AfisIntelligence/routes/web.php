<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisIntelligence\Http\Controllers\AfisIntelligenceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('afisintelligences', AfisIntelligenceController::class)->names('afisintelligence');
});
