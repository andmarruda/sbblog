<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SessionController;
use Illuminate\Support\Facades\Route;

// Account settings work before email verification, so a typo in the address can be fixed.
Route::middleware(['auth', 'auth.session'])->prefix('account')->group(function () {
    Route::redirect('/', '/account/profile');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:6,1')
        ->name('profile.destroy');

    Route::get('password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions', [SessionController::class, 'destroyOthers'])
        ->middleware('throttle:6,1')
        ->name('sessions.destroy-others');
});
