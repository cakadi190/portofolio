<?php

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.posts.create'))->assertRedirect(route('login'));
});

test('the editor pages render', function () {
    $post = Post::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.posts.create'))->assertOk();
    $this->actingAs($user)->get(route('admin.posts.edit', $post))->assertOk();
});

test('a post is created and the editor stays on its edit page', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Halo Dunia',
        'content' => '<p>Isi</p>',
        'is_published' => '0',
        'tags' => [$tag->id],
    ]);

    $post = Post::query()->where('title', 'Halo Dunia')->firstOrFail();

    $response->assertRedirect(route('admin.posts.edit', $post));
    expect($post->is_published)->toBeFalse()
        ->and($post->tags)->toHaveCount(1);
});

test('creating a post requires title and content', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), ['title' => '', 'content' => ''])
        ->assertSessionHasErrors(['title', 'content']);
});

test('a post can be updated', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.posts.update', $post), [
            'title' => 'Baru',
            'slug' => $post->slug,
            'content' => '<p>Baru</p>',
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.posts.edit', $post));

    expect($post->refresh())->title->toBe('Baru')->is_published->toBeTrue();
});

test('updating a post with an empty slug keeps the existing slug', function () {
    $post = Post::factory()->create(['slug' => 'slug-lama']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.posts.update', $post), [
            'title' => 'Baru',
            'slug' => '',
            'content' => '<p>Baru</p>',
            'is_published' => '0',
        ])
        ->assertRedirect(route('admin.posts.edit', $post));

    expect($post->refresh()->slug)->toBe('slug-lama');
});

test('a post can be deleted', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect(route('admin.posts.index'));

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
});

test('a trashed post can be restored or deleted permanently', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->create();

    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post));
    $this->actingAs($admin)->get(route('blog.show', $post))->assertNotFound();
    $this->actingAs($admin)->get(route('admin.posts.index', ['trashed' => 1]))->assertOk();

    $this->actingAs($admin)
        ->patch(route('admin.posts.restore', $post))
        ->assertRedirect(route('admin.posts.index', ['trashed' => 1]));
    expect($post->fresh()->trashed())->toBeFalse();

    $post->delete();
    $this->actingAs($admin)->delete(route('admin.posts.destroy', $post));

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('the list searches and sorts', function () {
    Post::factory()->create(['title' => 'Alpha']);
    Post::factory()->create(['title' => 'Bravo']);
    Post::factory()->create(['title' => 'Charlie']);
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('admin.posts.index', ['search' => 'bra']))
        ->assertInertia(fn ($page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Bravo')
            ->where('filters.search', 'bra'));

    $this->actingAs($user)
        ->get(route('admin.posts.index', ['sort' => 'title', 'direction' => 'desc']))
        ->assertInertia(fn ($page) => $page
            ->where('posts.data.0.title', 'Charlie')
            ->where('filters.sort', 'title'));

    $this->actingAs($user)
        ->get(route('admin.posts.index', ['sort' => 'content; drop table posts']))
        ->assertInertia(fn ($page) => $page->where('filters.sort', null));
});

test('the list paginates with a whitelisted page size', function () {
    Post::factory()->count(12)->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.posts.index'))
        ->assertInertia(fn ($page) => $page->has('posts.data', 10)->where('posts.last_page', 2));

    $this->actingAs($user)->get(route('admin.posts.index', ['per_page' => 7]))
        ->assertInertia(fn ($page) => $page->where('filters.per_page', 10));
});

test('a new post defaults to the signed in user as its author', function () {
    $user = User::factory()->redaktur()->create();

    $this->actingAs($user)->post(route('admin.posts.store'), ['title' => 'Tulisanku', 'content' => '<p>Isi</p>']);

    expect(Post::query()->where('title', 'Tulisanku')->firstOrFail()->user_id)->toBe($user->id);
});

test('the author can be changed from the post list', function () {
    $post = Post::factory()->create();
    $author = User::factory()->redaktur()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('admin.posts.author.update', $post), ['user_id' => $author->id])
        ->assertRedirect();

    expect($post->refresh()->author->is($author))->toBeTrue();
});

test('a regular user cannot become a post author', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('admin.posts.author.update', $post), ['user_id' => User::factory()->create()->id])
        ->assertSessionHasErrors('user_id');
});

test('tags and categories can be created on the fly from the editor and reused on a post', function () {
    $admin = User::factory()->admin()->create();

    $tag = $this->actingAs($admin)->postJson(route('admin.tags.quick'), ['name' => 'Baru'])
        ->assertCreated()->json();
    $category = $this->actingAs($admin)->postJson(route('admin.post-categories.quick'), ['name' => 'Kategori Baru'])
        ->assertCreated()->json();

    $this->assertDatabaseHas('tags', ['id' => $tag['id'], 'name' => 'Baru']);
    $this->assertDatabaseHas('post_categories', ['id' => $category['id'], 'name' => 'Kategori Baru']);

    $this->actingAs($admin)->postJson(route('admin.tags.quick'), ['name' => 'Baru'])
        ->assertOk()->assertJsonPath('id', $tag['id']);

    $this->actingAs($admin)->post(route('admin.posts.store'), [
        'title' => 'Dengan taksonomi baru',
        'content' => '<p>Isi</p>',
        'is_published' => '0',
        'tags' => [$tag['id']],
        'categories' => [$category['id']],
    ])->assertRedirect();

    expect(Post::query()->where('title', 'Dengan taksonomi baru')->first()->tags->pluck('id')->all())->toBe([$tag['id']]);
    $this->actingAs($admin)->postJson(route('admin.tags.quick'), ['name' => ''])->assertUnprocessable();
});

test('publishing without a date goes live now', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Langsung Tayang',
        'content' => '<p>Isi</p>',
        'is_published' => '1',
        'published_at' => '',
    ]);

    $post = Post::query()->where('title', 'Langsung Tayang')->firstOrFail();

    expect($post->isLive())->toBeTrue()->and($post->published_at)->not->toBeNull();
});

test('a future publication date schedules the post', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Tayang Nanti',
        'content' => '<p>Isi</p>',
        'is_published' => '1',
        'published_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
    ]);

    $post = Post::query()->where('title', 'Tayang Nanti')->firstOrFail();

    expect($post->isScheduled())->toBeTrue()
        ->and($post->isLive())->toBeFalse()
        ->and(Post::query()->published()->whereKey($post->id)->exists())->toBeFalse();

    $this->get(route('blog.show', $post))->assertNotFound();
});

test('a scheduled post goes public once its date has passed', function () {
    $post = Post::factory()->create(['is_published' => true, 'published_at' => now()->addHour()]);

    $this->get(route('blog.show', $post))->assertNotFound();

    $this->travel(2)->hours();

    $this->get(route('blog.show', $post))->assertOk();
});

test('saving a draft keeps its categories and tags', function () {
    $tag = Tag::factory()->create();
    $category = PostCategory::query()->create(['name' => 'Teknologi']);

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Draf Berelasi',
        'content' => '<p>Isi</p>',
        'is_published' => '0',
        'tags' => [$tag->id],
        'categories' => [$category->id],
    ]);

    $post = Post::query()->where('title', 'Draf Berelasi')->firstOrFail();

    expect($post->tags->pluck('id')->all())->toBe([$tag->id])
        ->and($post->categories->pluck('id')->all())->toBe([$category->id]);
});
