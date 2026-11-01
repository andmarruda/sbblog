<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');

// Placeholder until authentication (A-01) and tenancy (T-02) land.
Route::inertia('/dashboard', 'dashboard')->name('dashboard');
