<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\AuthenticateFrontend;
use Illuminate\Support\Facades\Route;

// Frontends pull the publishing log with their sync token (docs/v2/events.md).
Route::middleware([AuthenticateFrontend::class, 'throttle:sync'])->prefix('sync')->group(function () {
    Route::get('/', [SyncController::class, 'events'])->name('sync.events');
    Route::post('ack', [SyncController::class, 'ack'])->name('sync.ack');
    Route::get('snapshot', [SyncController::class, 'snapshot'])->name('sync.snapshot');
});
