<?php

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;

test('the sidebar lists five other published posts, never the current or a draft', function () {
    $current = Post::factory()->create(['published_at' => now()->subDay()]);
    $others = Post::factory()->count(6)->sequence(fn ($sequence) => ['published_at' => now()->subHours($sequence->index + 2)])->create();
    Post::factory()->create(['is_published' => false]);

    $this->get(route('blog.show', $current))->assertInertia(fn ($page) => $page
        ->has('otherPosts', 5)
        ->where('otherPosts.0.slug', $others[0]->slug)
        ->where('otherPosts', fn ($posts) => ! collect($posts)->pluck('slug')->contains($current->slug)));
});

test('the sidebar categories count only published posts and skip empty ones', function () {
    $used = PostCategory::create(['name' => 'Teknologi', 'color' => '#111111']);
    $empty = PostCategory::create(['name' => 'Kosong']);
    $post = Post::factory()->create();
    $post->categories()->attach($used);
    Post::factory()->create(['is_published' => false])->categories()->attach($used);

    $this->get(route('blog.show', $post))->assertInertia(fn ($page) => $page
        ->has('categories', 1)
        ->where('categories.0.name', 'Teknologi')
        ->where('categories.0.count', 1));

    expect($empty->posts()->count())->toBe(0);
});

test('previous and next link to the older and newer published posts', function () {
    $oldest = Post::factory()->create(['published_at' => now()->subDays(3)]);
    $middle = Post::factory()->create(['published_at' => now()->subDays(2)]);
    $newest = Post::factory()->create(['published_at' => now()->subDay()]);
    Post::factory()->create(['is_published' => false, 'published_at' => now()->subHours(36)]);

    $this->get(route('blog.show', $middle))->assertInertia(fn ($page) => $page
        ->where('adjacent.previous.slug', $oldest->slug)
        ->where('adjacent.next.slug', $newest->slug));

    $this->get(route('blog.show', $oldest))->assertInertia(fn ($page) => $page
        ->where('adjacent.previous', null)
        ->where('adjacent.next.slug', $middle->slug));
});

test('the blog list can be narrowed to one category', function () {
    $category = PostCategory::create(['name' => 'Teknologi']);
    Post::factory()->create(['title' => 'Dalam kategori'])->categories()->attach($category);
    Post::factory()->create(['title' => 'Di luar kategori']);

    $this->get(route('blog.index', ['category' => $category->id]))->assertInertia(fn ($page) => $page
        ->has('posts.data', 1)
        ->where('posts.data.0.title', 'Dalam kategori')
        ->where('activeCategory.name', 'Teknologi'));
});

test('the post detail payload carries the author avatar', function () {
    $author = User::factory()->create(['avatar' => 'avatars/penulis.webp']);
    $post = Post::factory()->create(['user_id' => $author->id, 'is_published' => true, 'published_at' => now()->subDay()]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('post.author.avatar', 'avatars/penulis.webp'));
});
