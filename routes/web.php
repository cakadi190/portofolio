<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}', [SocialiteController::class, 'redirect'])->name('auth.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.callback');
});

require __DIR__.'/settings.php';
