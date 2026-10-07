<?php

use App\Models\Tag;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
});

test('authenticated users can list tags', function () {
    Tag::factory()->count(3)->create();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.tags.index'));

    $response->assertOk();
});

test('a tag can be created', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.tags.store'), ['name' => 'Laravel']);

    $response->assertRedirect(route('admin.tags.index'));
    $this->assertDatabaseHas('tags', ['name' => 'Laravel']);
});

test('creating a tag requires a name', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.tags.store'), ['name' => '']);

    $response->assertSessionHasErrors('name');
});

test('a tag can be updated', function () {
    $tag = Tag::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.tags.update', $tag), ['name' => 'New Name']);

    $response->assertRedirect(route('admin.tags.index'));
    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New Name']);
});

test('a tag can be deleted', function () {
    $tag = Tag::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.tags.destroy', $tag));

    $response->assertRedirect(route('admin.tags.index'));
    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
