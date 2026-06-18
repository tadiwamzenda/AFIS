<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmWorkflows\Http\Controllers\AdmmWorkflowsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('admmworkflows', AdmmWorkflowsController::class)->names('admmworkflows');
});
