<?php

use Illuminate\Support\Facades\Route;
use Modules\AfisPipeline\Http\Controllers\PipelineController;

Route::middleware('auth')->group(function () {
    Route::get('/admin/afis/pipeline', [PipelineController::class, 'index'])
        ->name('admin.afis.pipeline');
});