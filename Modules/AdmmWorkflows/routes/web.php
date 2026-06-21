<?php

use Illuminate\Support\Facades\Route;
use Modules\AdmmWorkflows\Http\Controllers\WorkflowController;

Route::middleware('auth')->prefix('admin/admm/workflows')->name('admin.admm.workflows.')->group(function () {
    Route::get('/',              [WorkflowController::class, 'hub'])->name('hub');
    Route::get('/sim-swap',      [WorkflowController::class, 'simSwap'])->name('sim-swap');
    Route::get('/device-install',[WorkflowController::class, 'deviceInstall'])->name('device-install');
    Route::get('/device-remove', [WorkflowController::class, 'deviceRemove'])->name('device-remove');
});