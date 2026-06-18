<?php

use Illuminate\Support\Facades\Route;
use Modules\NavixyClient\Http\Controllers\NavixyClientController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('navixyclients', NavixyClientController::class)->names('navixyclient');
});
