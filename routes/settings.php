<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\TokenController;
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

    Route::get('tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('tokens', [TokenController::class, 'store'])->middleware('throttle:10,1')->name('tokens.store');
    Route::delete('tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token')->name('tokens.destroy');

    Route::middleware('password.confirm')->prefix('security')->name('security.')->group(function () {
        Route::get('/', [SecurityController::class, 'edit'])->name('edit');
        Route::post('two-factor', [SecurityController::class, 'enableTotp'])->name('two-factor.enable');
        Route::post('two-factor/confirm', [SecurityController::class, 'confirmTotp'])->middleware('throttle:10,1')->name('two-factor.confirm');
        Route::delete('two-factor', [SecurityController::class, 'disableTotp'])->name('two-factor.disable');
        Route::post('recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
        Route::post('passkeys/options', [SecurityController::class, 'passkeyOptions'])->middleware('throttle:20,1')->name('passkeys.options');
        Route::post('passkeys', [SecurityController::class, 'storePasskey'])->middleware('throttle:10,1')->name('passkeys.store');
        Route::delete('passkeys/{passkey}', [SecurityController::class, 'destroyPasskey'])->whereUuid('passkey')->name('passkeys.destroy');
        Route::delete('devices/{device}', [SecurityController::class, 'destroyDevice'])->whereUuid('device')->name('devices.destroy');
        Route::delete('devices', [SecurityController::class, 'destroyDevices'])->name('devices.destroy-all');
    });

    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions', [SessionController::class, 'destroyOthers'])
        ->middleware('throttle:6,1')
        ->name('sessions.destroy-others');
});
