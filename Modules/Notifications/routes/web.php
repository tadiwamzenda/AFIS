<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationController;
use Modules\Notifications\Http\Controllers\NotificationReportController;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/report', [NotificationReportController::class, 'generate'])->name('notifications.report');
});