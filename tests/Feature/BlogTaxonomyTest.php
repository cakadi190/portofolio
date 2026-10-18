<?php

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function publishedPostIn(PostCategory|Tag $taxonomy, array $attributes = []): Post
{
    $post = Post::factory()->create($attributes);
    $taxonomy instanceof PostCategory ? $post->categories()->attach($taxonomy) : $post->tags()->attach($taxonomy);

    return $post;
}

test('a category page lists only its published posts, with the published count', function () {
    $category = PostCategory::create(['name' => 'Laravel Tips']);
    $published = publishedPostIn($category);
    publishedPostIn($category, ['is_published' => false]);
    Post::factory()->create();

    expect($category->slug)->toBe('laravel-tips');

    $this->get(route('blog.categories.show', $category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('blog/category')
            ->where('category.name', 'Laravel Tips')
            ->where('posts.total', 1)
            ->where('posts.data.0.slug', $published->slug)
            ->where('posts.data.0.editUrl', null)
            ->missing('posts.data.0.id'));
});

test('a tag page lists only its published posts', function () {
    $tag = Tag::create(['name' => 'Eloquent']);
    $published = publishedPostIn($tag);
    publishedPostIn($tag, ['is_published' => false]);

    $this->get(route('blog.tags.show', $tag))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('blog/tag')
            ->where('posts.total', 1)
            ->where('posts.data.0.slug', $published->slug)
            ->where('posts.data.0.tags.0.slug', 'eloquent'));
});

test('an author page lists published posts and exposes no private account data', function () {
    $author = User::factory()->redaktur()->create(['name' => 'Penulis Satu']);
    $published = Post::factory()->for($author, 'author')->create();
    Post::factory()->for($author, 'author')->create(['is_published' => false]);

    $this->get(route('blog.authors.show', $author))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('blog/author')
            ->where('author.name', 'Penulis Satu')
            ->where('posts.total', 1)
            ->where('posts.data.0.slug', $published->slug)
            ->has('author', fn (Assert $props) => $props->hasAll(['name', 'username', 'avatar', 'bio'])))
        ->assertDontSee($author->email);
});

test('empty taxonomies and authors without posts show an empty list', function () {
    $category = PostCategory::create(['name' => 'Kosong']);
    $staff = User::factory()->redaktur()->create();

    $this->get(route('blog.categories.show', $category))
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 0)->where('posts.data', []));
    $this->get(route('blog.authors.show', $staff))
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 0));
});

test('unknown slugs are a 404, and a regular account is not a public author', function () {
    $this->get('/blog/categories/tidak-ada')->assertNotFound();
    $this->get('/blog/tags/tidak-ada')->assertNotFound();
    $this->get('/blog/authors/tidak-ada')->assertNotFound();
    $this->get(route('blog.authors.show', User::factory()->create()))->assertNotFound();
    $this->get('/blog/tidak-ada')->assertNotFound();
});

test('a user who only has a draft is not a public author', function () {
    $user = User::factory()->create();
    Post::factory()->for($user, 'author')->create(['is_published' => false]);

    $this->get(route('blog.authors.show', $user))->assertNotFound();
});

test('pagination follows the same published rules', function () {
    $category = PostCategory::create(['name' => 'Banyak']);

    foreach (range(1, 11) as $_) {
        publishedPostIn($category);
    }
    publishedPostIn($category, ['is_published' => false]);

    $this->get(route('blog.categories.show', $category))
        ->assertInertia(fn (Assert $page) => $page->where('posts.total', 11)->where('posts.last_page', 2)->has('posts.data', 9));
    $this->get(route('blog.categories.show', [$category, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

test('slugs are unique when names collide', function () {
    $first = Tag::create(['name' => 'PHP']);
    $second = Tag::create(['name' => 'php']);

    expect($first->slug)->toBe('php')->and($second->slug)->toBe('php-1');
});

test('taxonomy pages set their own title, canonical url and structured data', function () {
    $category = PostCategory::create(['name' => 'Laravel Tips']);

    $this->get(route('blog.categories.show', $category))
        ->assertSee('<title>Kategori Laravel Tips', false)
        ->assertSee('rel="canonical" href="'.route('blog.categories.show', $category).'"', false)
        ->assertSee('CollectionPage', false);

    $author = User::factory()->redaktur()->create();
    $this->get(route('blog.authors.show', $author))->assertSee('ProfilePage', false);
});

test('the edit shortcut only reaches the owner and admins, on the post and on every listing', function () {
    $category = PostCategory::create(['name' => 'Laravel Tips']);
    $post = publishedPostIn($category);
    $editUrl = route('admin.posts.edit', $post, absolute: false);

    foreach ([route('blog.show', $post), route('blog.categories.show', $category), route('blog.index')] as $url) {
        $this->get($url)->assertOk()->assertDontSee($editUrl, false);
        $this->actingAs(User::factory()->create())->get($url)->assertOk()->assertDontSee($editUrl, false);
    }

    $this->actingAs(User::factory()->redaktur()->create());
    $this->get(route('blog.show', $post))->assertInertia(fn (Assert $page) => $page->where('post.editUrl', null));

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('blog.show', $post))->assertInertia(fn (Assert $page) => $page->where('post.editUrl', $editUrl));
    $this->get(route('blog.categories.show', $category))
        ->assertInertia(fn (Assert $page) => $page->where('posts.data.0.editUrl', $editUrl));
});

test('the editor and update endpoints stay protected whatever the frontend shows', function () {
    $post = Post::factory()->create();

    $this->get(route('admin.posts.edit', $post))->assertRedirect();
    $this->actingAs(User::factory()->create())->get(route('admin.posts.edit', $post))->assertForbidden();
    $this->actingAs(User::factory()->create())->put(route('admin.posts.update', $post), ['title' => 'Diretas'])->assertForbidden();
    $this->actingAs(User::factory()->redaktur()->create())->get(route('admin.posts.edit', $post))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.posts.edit', $post))->assertOk();
});

test('drafts stay hidden on the post page for the public', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->get(route('blog.show', $post))->assertNotFound();
});

test('the post page links to its category, tag and author archives', function () {
    $author = User::factory()->redaktur()->create();
    $category = PostCategory::create(['name' => 'Laravel Tips']);
    $tag = Tag::create(['name' => 'Eloquent']);
    $post = Post::factory()->for($author, 'author')->create();
    $post->categories()->attach($category);
    $post->tags()->attach($tag);

    $this->get(route('blog.show', $post))->assertInertia(fn (Assert $page) => $page
        ->where('post.categories.0.slug', 'laravel-tips')
        ->where('post.tags.0.slug', 'eloquent')
        ->where('post.author.username', $author->username));
});

test('author pages live at /blog/@username, show the sanitized bio, and the old url redirects', function () {
    $author = User::factory()->redaktur()->create([
        'username' => 'cak.adi',
        'bio' => '<p>Penulis <strong>teknologi</strong></p><script>alert(1)</script>',
    ]);
    Post::factory()->for($author, 'author')->create();

    expect(route('blog.authors.show', $author, absolute: false))->toBe('/blog/@cak.adi');

    $this->get('/blog/@cak.adi')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('author.username', 'cak.adi')
            ->where('author.bio', fn ($bio) => str_contains($bio, '<strong>teknologi</strong>') && ! str_contains($bio, 'script')));

    $this->get('/blog/authors/cak.adi')->assertRedirect('/blog/@cak.adi')->assertStatus(301);
});
