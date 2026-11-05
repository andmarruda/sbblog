<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Instance administration, above workspaces. Confirming the password guards admin grants.
Route::middleware(['auth', 'auth.session', 'verified', 'can:admin', 'password.confirm'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/users');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::put('users/{user}/admin', [UserController::class, 'updateAdmin'])
            ->middleware('throttle:20,1')
            ->whereUuid('user')
            ->name('users.admin');
    });
