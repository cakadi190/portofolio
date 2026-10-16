<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogTaxonomyController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\ClarificationController;
use App\Http\Controllers\CoffeeShopController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevCommunityController;
use App\Http\Controllers\DisclaimerController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\GuestbookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PdfTrustController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PortfolioReviewController;
use App\Http\Controllers\ProgrammerChannelController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpeakingController;
use App\Http\Controllers\StatisticController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::name('sitemaps.')->controller(SitemapController::class)->group(function () {
    Route::get('sitemap.xml', 'index')->name('index');
    Route::get('sitemap-pages.xml', 'pages')->name('pages');
    Route::get('sitemap-posts.xml', 'posts')->name('posts');
    Route::get('sitemap-portfolios.xml', 'portfolios')->name('portfolios');
    Route::get('sitemap-clarifications.xml', 'clarifications')->name('clarifications');
    Route::get('sitemap-notes.xml', 'notes')->name('notes');
    Route::get('sitemap-services.xml', 'services')->name('services');
    Route::get('sitemap.xsl', 'style')->name('style');
});
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('resume', [ResumeController::class, 'show'])->name('resume.show');
Route::get('resume.pdf', [ResumeController::class, 'pdf'])->name('resume.pdf');
Route::get('resume/{profile}.pdf', [ResumeController::class, 'pdf'])->name('resume.profile.pdf');
Route::get('resume/{profile}', [ResumeController::class, 'show'])->name('resume.profile');
Route::get('career', [CareerController::class, 'index'])->name('career.index');
Route::get('education', [EducationController::class, 'index'])->name('education.index');
Route::get('awards', [AwardController::class, 'index'])->name('awards.index');
Route::get('speaking', [SpeakingController::class, 'index'])->name('speaking.index');
Route::get('disclaimer', DisclaimerController::class)->name('disclaimer.index');
Route::get('contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('categories/{category:slug}', [BlogTaxonomyController::class, 'category'])->name('categories.show');
    Route::get('tags/{tag:slug}', [BlogTaxonomyController::class, 'tag'])->name('tags.show');
    Route::get('@{author:username}', [BlogTaxonomyController::class, 'author'])->name('authors.show');
    Route::get('authors/{author:username}', fn (User $author) => redirect()->route('blog.authors.show', $author, 301));
    Route::get('{post:slug}', [BlogController::class, 'show'])->name('show');
    Route::post('{post:slug}/comments', [BlogCommentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('comments.store');
});

Route::prefix('notes')->name('notes.')->group(function () {
    Route::get('/', [NoteController::class, 'index'])->name('index');
    Route::get('{note:slug}', [NoteController::class, 'show'])->name('show');
    Route::get('{note:slug}/markdown', [NoteController::class, 'markdown'])->name('markdown');
});

Route::get('statistic', [StatisticController::class, 'index'])->name('statistic.index');
Route::any('statistics', LegacyRedirectController::class)->defaults('destination', '/statistic');

Route::get('guestbook', [GuestbookController::class, 'index'])->name('guestbook.index');
Route::post('guestbook', [GuestbookController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('guestbook.store');

Route::prefix('clarifications')->name('clarifications.')->group(function () {
    Route::get('/', [ClarificationController::class, 'index'])->name('index');
    Route::get('{clarification:slug}', [ClarificationController::class, 'show'])->name('show');
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

Route::post('locale', [LocaleController::class, 'update'])->middleware('throttle:30,1')->name('locale.update');
Route::post('pdf-trust', PdfTrustController::class)->middleware('throttle:60,1')->name('pdf-trust');
Route::get('certifications/{slug}', [CertificationController::class, 'show'])->name('certifications.show');

Route::prefix('resources')->name('resources.')->group(function () {
    Route::get('coffee-shops', [CoffeeShopController::class, 'index'])->name('coffee-shops.index');
    Route::get('dev-communities', [DevCommunityController::class, 'index'])->name('dev-communities.index');
    Route::get('programmer-channels', [ProgrammerChannelController::class, 'index'])->name('programmer-channels.index');
});

// Legacy Indonesian URLs, permanently redirected so indexed links keep working.
Route::any('certification/{slug}', LegacyRedirectController::class)->defaults('destination', '/certifications/{slug}');
Route::any('certificates/{slug}', LegacyRedirectController::class)->defaults('destination', '/certifications/{slug}');
Route::any('karir', LegacyRedirectController::class)->defaults('destination', '/career');
Route::any('pendidikan', LegacyRedirectController::class)->defaults('destination', '/education');
Route::any('penghargaan', LegacyRedirectController::class)->defaults('destination', '/awards');
Route::any('kontak', LegacyRedirectController::class)->defaults('destination', '/contact');
Route::any('layanan', LegacyRedirectController::class)->defaults('destination', '/services');
Route::any('layanan/{slug}', LegacyRedirectController::class)->defaults('destination', '/services/{slug}');
Route::any('portofolio', LegacyRedirectController::class)->defaults('destination', '/portfolio');
Route::any('portofolio/{slug}', LegacyRedirectController::class)->defaults('destination', '/portfolio/{slug}');
Route::any('tentang/saya', LegacyRedirectController::class)->defaults('destination', '/about');
Route::any('about/me', LegacyRedirectController::class)->defaults('destination', '/about');
Route::any('tentang/situs', LegacyRedirectController::class)->defaults('destination', '/about/site');
Route::any('tentang/skill', LegacyRedirectController::class)->defaults('destination', '/about/skills');
Route::any('sumber-daya/tempat-ngopi', LegacyRedirectController::class)->defaults('destination', '/resources/coffee-shops');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin', DashboardController::class)->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
