<?php

use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CertificationController;
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
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    $only = ['index', 'store', 'update', 'destroy'];

    Route::resource('awards', AwardController::class)->parameters(['awards' => 'award'])->only($only);
    Route::resource('careers', CareerController::class)->parameters(['careers' => 'career'])->only($only);
    Route::resource('certifications', CertificationController::class)->parameters(['certifications' => 'certification'])->only($only);
    Route::resource('coffee-places', CoffeePlaceController::class)->parameters(['coffee-places' => 'coffeePlace'])->only($only);
    Route::resource('educations', EducationController::class)->parameters(['educations' => 'education'])->only($only);
    Route::resource('organizations', OrganizationController::class)->parameters(['organizations' => 'organization'])->only($only);
    Route::resource('portfolios', PortfolioController::class)->parameters(['portfolios' => 'portfolio'])->only($only);
    Route::resource('portfolio-categories', PortfolioCategoryController::class)->parameters(['portfolio-categories' => 'portfolioCategory'])->only($only);
    Route::resource('portfolio-galleries', PortfolioGalleryController::class)->parameters(['portfolio-galleries' => 'portfolioGallery'])->only($only);
    Route::resource('portfolio-ratings', PortfolioRatingController::class)->parameters(['portfolio-ratings' => 'portfolioRating'])->only($only);
    Route::resource('post-categories', PostCategoryController::class)->parameters(['post-categories' => 'postCategory'])->only($only);
    Route::resource('posts', PostController::class)->parameters(['posts' => 'post'])->only($only);
    Route::resource('tags', TagController::class)->parameters(['tags' => 'tag'])->only($only);
    Route::resource('technologies', TechnologyController::class)->parameters(['technologies' => 'technology'])->only($only);
    Route::resource('users', UserController::class)->parameters(['users' => 'user'])->only($only);
});
