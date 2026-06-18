<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDocuments\Http\Controllers\AdmmDocumentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admmdocuments', AdmmDocumentsController::class)->names('admmdocuments');
});
