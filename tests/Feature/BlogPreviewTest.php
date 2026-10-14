<?php

use App\Models\Post;
use App\Models\User;

test('a draft is a 404 without the preview parameters', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('blog.show', $post))
        ->assertNotFound();
});

test('staff open a draft through its preview url, marked noindex', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->get($post->previewUrl())
        ->assertOk()
        ->assertSee('noindex,nofollow', false);
});

test('a draft preview rejects guests, regular users and a wrong hash', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->get($post->previewUrl())->assertNotFound();
    $this->actingAs(User::factory()->create())->get($post->previewUrl())->assertNotFound();
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('blog.show', [$post, 'action' => 'preview', 'cache' => 'salah']))
        ->assertNotFound();
});

test('saving the draft invalidates the old preview url', function () {
    $post = Post::factory()->create(['is_published' => false]);
    $oldUrl = $post->previewUrl();

    $this->travel(1)->minute();
    $post->update(['title' => 'Judul baru']);

    $this->actingAs(User::factory()->admin()->create())->get($oldUrl)->assertNotFound();
    $this->actingAs(User::factory()->admin()->create())->get($post->previewUrl())->assertOk();
});
