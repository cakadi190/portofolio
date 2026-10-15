<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\CalendarEventController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\ClarificationController;
use App\Http\Controllers\Admin\CoffeePlaceController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DevCommunityController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\GuestbookEntryController;
use App\Http\Controllers\Admin\LinkPreviewController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PortfolioGalleryController;
use App\Http\Controllers\Admin\PortfolioRatingController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProgrammerChannelController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SpeakingEngagementController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TechnologyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitorAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    $only = ['index', 'store', 'update', 'destroy'];

    // Blogging area: admins and redaktur.
    Route::middleware('can:manage-blog')->group(function () use ($only) {
        Route::get('calendar-events', CalendarEventController::class)->name('calendar-events');
        Route::resource('blog-comments', BlogCommentController::class)->parameters(['blog-comments' => 'blogComment'])->only(['index', 'show', 'update', 'destroy']);
        Route::post('post-categories/quick', [PostCategoryController::class, 'quick'])->name('post-categories.quick');
        Route::post('tags/quick', [TagController::class, 'quick'])->name('tags.quick');
        Route::resource('post-categories', PostCategoryController::class)->parameters(['post-categories' => 'postCategory'])->only($only);
        Route::patch('posts/{post}/author', [PostController::class, 'updateAuthor'])->name('posts.author.update');
        Route::patch('posts/{post}/restore', [PostController::class, 'restore'])->withTrashed()->name('posts.restore');
        Route::resource('posts', PostController::class)->parameters(['posts' => 'post'])->only([...$only, 'create', 'edit'])->withTrashed(['destroy']);
        Route::resource('notes', NoteController::class)->parameters(['notes' => 'note'])->only([...$only, 'create', 'edit']);
        Route::resource('guestbook-entries', GuestbookEntryController::class)->parameters(['guestbook-entries' => 'guestbookEntry'])->only(['index', 'update', 'destroy']);
        Route::post('guestbook-entries/{guestbookEntry}/reply', [GuestbookEntryController::class, 'reply'])->name('guestbook-entries.reply');
        Route::resource('tags', TagController::class)->parameters(['tags' => 'tag'])->only($only);

        // The post editor's media picker and uploader.
        Route::get('media/browse', [MediaController::class, 'browse'])->name('media.browse');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::post('link-preview', LinkPreviewController::class)->middleware('throttle:30,1')->name('link-preview');
    });

    // Everything else: admins only.
    Route::middleware('can:manage-site')->group(function () use ($only) {
        Route::resource('awards', AwardController::class)->parameters(['awards' => 'award'])->only($only);
        Route::resource('careers', CareerController::class)->parameters(['careers' => 'career'])->only($only);
        Route::resource('certifications', CertificationController::class)->parameters(['certifications' => 'certification'])->only($only);
        Route::get('clarifications/{clarification}/preview', [ClarificationController::class, 'preview'])->name('clarifications.preview');
        Route::resource('clarifications', ClarificationController::class)->parameters(['clarifications' => 'clarification'])->only([...$only, 'create', 'edit']);
        Route::resource('dev-communities', DevCommunityController::class)->parameters(['dev-communities' => 'devCommunity'])->only($only);
        Route::resource('programmer-channels', ProgrammerChannelController::class)->parameters(['programmer-channels' => 'programmerChannel'])->only($only);
        Route::resource('coffee-places', CoffeePlaceController::class)->parameters(['coffee-places' => 'coffeePlace'])->only($only);
        Route::get('system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
        Route::post('system-settings/test', [SystemSettingController::class, 'testConnection'])->middleware('throttle:6,1')->name('system-settings.test');
        Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->middleware('throttle:6,1')->name('activity-logs.export');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activity}', [ActivityLogController::class, 'show'])->whereNumber('activity')->name('activity-logs.show');
        Route::get('visitor-analytics', [VisitorAnalyticsController::class, 'index'])->name('visitor-analytics.index');
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
        Route::post('technologies/quick', [TechnologyController::class, 'quick'])->name('technologies.quick');
        Route::resource('technologies', TechnologyController::class)->parameters(['technologies' => 'technology'])->only($only);
        Route::resource('users', UserController::class)->parameters(['users' => 'user'])->only($only);
    });
});
