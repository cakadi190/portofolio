<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CoffeeShopController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PortfolioReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::name('sitemaps.')->controller(SitemapController::class)->group(function () {
    Route::get('sitemap.xml', 'index')->name('index');
    Route::get('sitemap-pages.xml', 'pages')->name('pages');
    Route::get('sitemap-posts.xml', 'posts')->name('posts');
    Route::get('sitemap-portfolios.xml', 'portfolios')->name('portfolios');
    Route::get('sitemap.xsl', 'style')->name('style');
});
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('karir', [CareerController::class, 'index'])->name('career.index');
Route::get('pendidikan', [EducationController::class, 'index'])->name('education.index');
Route::get('penghargaan', [AwardController::class, 'index'])->name('awards.index');
Route::get('kontak', [ContactController::class, 'index'])->name('contact.index');
Route::post('kontak', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('layanan', [ServiceController::class, 'index'])->name('services.index');
Route::get('layanan/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{post:slug}', [BlogController::class, 'show'])->name('show');
    Route::post('{post:slug}/komentar', [BlogCommentController::class, 'store'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('comments.store');
});

Route::prefix('portofolio')->name('portfolios.')->group(function () {
    Route::get('/', [PortfolioController::class, 'index'])->name('index');
    Route::get('{portfolio:slug}', [PortfolioController::class, 'show'])->name('show');
    Route::post('{portfolio:slug}/ulasan', [PortfolioReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reviews.store');
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
    Route::get('admin', DashboardController::class)->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
