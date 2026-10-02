<?php

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.posts.create'))->assertRedirect(route('login'));
});

test('the editor pages render', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.posts.create'))->assertOk();
    $this->actingAs($user)->get(route('admin.posts.edit', $post))->assertOk();
});

test('a post is created and the editor stays on its edit page', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs(User::factory()->create())->post(route('admin.posts.store'), [
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
    $this->actingAs(User::factory()->create())
        ->post(route('admin.posts.store'), ['title' => '', 'content' => ''])
        ->assertSessionHasErrors(['title', 'content']);
});

test('a post can be updated', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs(User::factory()->create())
        ->put(route('admin.posts.update', $post), [
            'title' => 'Baru',
            'slug' => $post->slug,
            'content' => '<p>Baru</p>',
            'is_published' => '1',
        ])
        ->assertRedirect(route('admin.posts.edit', $post));

    expect($post->refresh())->title->toBe('Baru')->is_published->toBeTrue();
});

test('a post can be deleted', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect(route('admin.posts.index'));

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('the list searches and sorts', function () {
    Post::factory()->create(['title' => 'Alpha']);
    Post::factory()->create(['title' => 'Bravo']);
    Post::factory()->create(['title' => 'Charlie']);
    $user = User::factory()->create();

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
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.posts.index'))
        ->assertInertia(fn ($page) => $page->has('posts.data', 10)->where('posts.last_page', 2));

    $this->actingAs($user)->get(route('admin.posts.index', ['per_page' => 7]))
        ->assertInertia(fn ($page) => $page->where('filters.per_page', 10));
});
