<?php

use App\Http\Controllers\Apps\OAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// Public: the story behind SBBlog and Sysborg.
Route::inertia('about', 'about')->name('about');

Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('invitations/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}', [InvitationAcceptController::class, 'accept'])
    ->middleware(['auth', 'auth.session', 'throttle:6,1'])
    ->name('invitations.accept');

Route::middleware(['auth', 'auth.session', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Apps ask for access here (OAuth authorization code); the token exchange is in routes/api.php.
    Route::get('oauth/authorize', [OAuthController::class, 'show'])->name('oauth.authorize');
    Route::post('oauth/authorize', [OAuthController::class, 'approve'])->middleware('throttle:20,1')->name('oauth.approve');

    Route::get('onboarding', [OnboardingController::class, 'create'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('onboarding.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
require __DIR__.'/tenant.php';
