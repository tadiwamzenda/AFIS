<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPipeline\Http\Controllers\AfisPipelineController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('afispipelines', AfisPipelineController::class)->names('afispipeline');
});
