<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPortal\Http\Controllers\AfisPortalController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('afisportals', AfisPortalController::class)->names('afisportal');
});
