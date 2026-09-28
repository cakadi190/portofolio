<?php

use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CoffeePlaceController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PortfolioCategoryController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PortfolioGalleryController;
use App\Http\Controllers\Admin\PortfolioRatingController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TechnologyController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('awards', AwardController::class)->parameters(['awards' => 'award'])->except('show');
    Route::resource('careers', CareerController::class)->parameters(['careers' => 'career'])->except('show');
    Route::resource('coffee-places', CoffeePlaceController::class)->parameters(['coffee-places' => 'coffeePlace'])->except('show');
    Route::resource('educations', EducationController::class)->parameters(['educations' => 'education'])->except('show');
    Route::resource('organizations', OrganizationController::class)->parameters(['organizations' => 'organization'])->except('show');
    Route::resource('portfolios', PortfolioController::class)->parameters(['portfolios' => 'portfolio'])->except('show');
    Route::resource('portfolio-categories', PortfolioCategoryController::class)->parameters(['portfolio-categories' => 'portfolioCategory'])->except('show');
    Route::resource('portfolio-galleries', PortfolioGalleryController::class)->parameters(['portfolio-galleries' => 'portfolioGallery'])->except('show');
    Route::resource('portfolio-ratings', PortfolioRatingController::class)->parameters(['portfolio-ratings' => 'portfolioRating'])->except('show');
    Route::resource('post-categories', PostCategoryController::class)->parameters(['post-categories' => 'postCategory'])->except('show');
    Route::resource('posts', PostController::class)->parameters(['posts' => 'post'])->except('show');
    Route::resource('tags', TagController::class)->parameters(['tags' => 'tag'])->except('show');
    Route::resource('technologies', TechnologyController::class)->parameters(['technologies' => 'technology'])->except('show');
});
