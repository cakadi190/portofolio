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
use App\Http\Controllers\SpeakingController;
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

Route::get('career', [CareerController::class, 'index'])->name('career.index');
Route::get('education', [EducationController::class, 'index'])->name('education.index');
Route::get('awards', [AwardController::class, 'index'])->name('awards.index');
Route::get('speaking', [SpeakingController::class, 'index'])->name('speaking.index');
Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('{post:slug}', [BlogController::class, 'show'])->name('show');
    Route::post('{post:slug}/comments', [BlogCommentController::class, 'store'])
        ->middleware(['auth', 'throttle:10,1'])
        ->name('comments.store');
});

Route::prefix('portfolio')->name('portfolios.')->group(function () {
    Route::get('/', [PortfolioController::class, 'index'])->name('index');
    Route::get('{portfolio:slug}', [PortfolioController::class, 'show'])->name('show');
    Route::post('{portfolio:slug}/reviews', [PortfolioReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reviews.store');
});

Route::prefix('about')->name('about.')->group(function () {
    Route::get('/', [AboutController::class, 'me'])->name('me');
    Route::get('site', [AboutController::class, 'site'])->name('site');
    Route::get('skills', [AboutController::class, 'skills'])->name('skills');
});

Route::prefix('resources')->name('resources.')->group(function () {
    Route::get('coffee-shops', [CoffeeShopController::class, 'index'])->name('coffee-shops.index');
});

// Legacy Indonesian URLs, permanently redirected so indexed links keep working.
Route::permanentRedirect('karir', '/career');
Route::permanentRedirect('pendidikan', '/education');
Route::permanentRedirect('penghargaan', '/awards');
Route::permanentRedirect('kontak', '/contact');
Route::permanentRedirect('layanan', '/services');
Route::permanentRedirect('layanan/{slug}', '/services/{slug}');
Route::permanentRedirect('portofolio', '/portfolio');
Route::permanentRedirect('portofolio/{slug}', '/portfolio/{slug}');
Route::permanentRedirect('tentang/saya', '/about');
Route::permanentRedirect('about/me', '/about');
Route::permanentRedirect('tentang/situs', '/about/site');
Route::permanentRedirect('tentang/skill', '/about/skills');
Route::permanentRedirect('sumber-daya/tempat-ngopi', '/resources/coffee-shops');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin', DashboardController::class)->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
