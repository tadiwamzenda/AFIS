<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmDocuments\Http\Controllers\AdmmDocumentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('admmdocuments', AdmmDocumentsController::class)->names('admmdocuments');
});
