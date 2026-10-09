<?php

use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\CoffeePlaceController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PortfolioGalleryController;
use App\Http\Controllers\Admin\PortfolioRatingController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SpeakingEngagementController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    $only = ['index', 'store', 'update', 'destroy'];

    // Blogging area: admins and redaktur.
    Route::middleware('can:manage-blog')->group(function () use ($only) {
        Route::resource('blog-comments', BlogCommentController::class)->parameters(['blog-comments' => 'blogComment'])->only(['index', 'show', 'update', 'destroy']);
        Route::resource('post-categories', PostCategoryController::class)->parameters(['post-categories' => 'postCategory'])->only($only);
        Route::resource('posts', PostController::class)->parameters(['posts' => 'post'])->only([...$only, 'create', 'edit']);
        Route::resource('tags', TagController::class)->parameters(['tags' => 'tag'])->only($only);

        // The post editor's media picker and uploader.
        Route::get('media/browse', [MediaController::class, 'browse'])->name('media.browse');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
    });

    // Everything else: admins only.
    Route::middleware('can:manage-site')->group(function () use ($only) {
        Route::resource('awards', AwardController::class)->parameters(['awards' => 'award'])->only($only);
        Route::resource('careers', CareerController::class)->parameters(['careers' => 'career'])->only($only);
        Route::resource('certifications', CertificationController::class)->parameters(['certifications' => 'certification'])->only($only);
        Route::resource('coffee-places', CoffeePlaceController::class)->parameters(['coffee-places' => 'coffeePlace'])->only($only);
        Route::get('system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
        Route::put('system-settings', [SystemSettingController::class, 'update'])->name('system-settings.update');
        Route::resource('contact-messages', ContactMessageController::class)->parameters(['contact-messages' => 'contactMessage'])->only(['index', 'update', 'destroy']);
        Route::resource('educations', EducationController::class)->parameters(['educations' => 'education'])->only($only);
        Route::resource('media', MediaController::class)->parameters(['media' => 'media'])->only(['index', 'update', 'destroy']);
        Route::resource('organizations', OrganizationController::class)->parameters(['organizations' => 'organization'])->only($only);
        Route::resource('portfolios', PortfolioController::class)->parameters(['portfolios' => 'portfolio'])->only([...$only, 'create', 'edit']);
        Route::resource('portfolio-galleries', PortfolioGalleryController::class)->parameters(['portfolio-galleries' => 'portfolioGallery'])->only($only);
        Route::resource('portfolio-ratings', PortfolioRatingController::class)->parameters(['portfolio-ratings' => 'portfolioRating'])->only($only);
        Route::resource('services', ServiceController::class)->parameters(['services' => 'service'])->only(['index', 'edit', 'update']);
        Route::resource('speaking-engagements', SpeakingEngagementController::class)->parameters(['speaking-engagements' => 'speakingEngagement'])->only($only);
        Route::resource('technologies', TechnologyController::class)->parameters(['technologies' => 'technology'])->only($only);
        Route::resource('users', UserController::class)->parameters(['users' => 'user'])->only($only);
    });
});
