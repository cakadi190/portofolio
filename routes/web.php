<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CoffeeShopController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('karir', [CareerController::class, 'index'])->name('career.index');
Route::get('pendidikan', [EducationController::class, 'index'])->name('education.index');
Route::get('penghargaan', [AwardController::class, 'index'])->name('awards.index');
Route::get('kontak', [ContactController::class, 'index'])->name('contact.index');
Route::get('layanan', [ServiceController::class, 'index'])->name('services.index');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{post:slug}', [BlogController::class, 'show'])->name('show');
});

Route::prefix('portofolio')->name('portfolios.')->group(function () {
    Route::get('/', [PortfolioController::class, 'index'])->name('index');
    Route::get('{portfolio:slug}', [PortfolioController::class, 'show'])->name('show');
});

Route::prefix('tentang')->name('about.')->group(function () {
    Route::get('saya', [AboutController::class, 'me'])->name('me');
    Route::get('situs', [AboutController::class, 'site'])->name('site');
    Route::get('skill', [AboutController::class, 'skills'])->name('skills');
});

Route::prefix('sumber-daya')->name('resources.')->group(function () {
    Route::get('tempat-ngopi', [CoffeeShopController::class, 'index'])->name('coffee-shops.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard/index')->name('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('auth/{provider}', [SocialiteController::class, 'redirect'])->name('auth.redirect');
    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.callback');
});

require __DIR__.'/settings.php';
