<?php

use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Post;
use App\Services\MediaService;
use Database\Seeders\CoffeePlaceSeeder;
use Database\Seeders\EducationSeeder;
use Database\Seeders\PortfolioCategorySeeder;
use Database\Seeders\PortfolioSeeder;
use Database\Seeders\PostCategorySeeder;
use Database\Seeders\PostSeeder;
use Database\Seeders\TechnologySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    $this->seed(UserSeeder::class);
});

it('registers seeded education logos in the media library', function (): void {
    $this->seed(EducationSeeder::class);

    expect(Media::query()->count())->toBe(2);
    Media::query()->each(fn (Media $media) => Storage::disk('public')->assertExists($media->path));
});

it('registers seeded coffee place and portfolio images in the media library', function (): void {
    $this->seed([CoffeePlaceSeeder::class, TechnologySeeder::class, PortfolioCategorySeeder::class, PortfolioSeeder::class]);

    $portfolio = Portfolio::query()->first();

    expect(Media::query()->where('path', $portfolio->image)->exists())->toBeTrue()
        ->and(app(MediaService::class)->usageCount(Media::query()->where('path', $portfolio->image)->first()))->toBe(1);
});

it('seeds posts with editor blocks and resolves the cover image token', function (): void {
    $this->seed([PostCategorySeeder::class, PostSeeder::class]);

    $post = Post::query()->first();

    expect($post->content)->toContain('wp-block-image')
        ->and($post->content)->toContain('wp-block-callout')
        ->and($post->content)->not->toContain('{{cover}}')
        ->and($post->content)->toContain('/storage/'.$post->cover_image);
});
