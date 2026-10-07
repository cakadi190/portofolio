<?php

use App\Models\Media;
use App\Models\Service;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.services.index'))->assertRedirect(route('login'));
});

test('the index and edit pages render', function () {
    $service = Service::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get(route('admin.services.index'))->assertOk();
    $this->actingAs($user)->get(route('admin.services.edit', $service))->assertOk();
});

test('a service can be updated', function () {
    $service = Service::factory()->create();
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.services.update', $service), [
        'name' => 'Website Baru',
        'slug' => 'website-baru',
        'color' => '#112233',
        'image' => $media->path,
        'description' => '<p>Deskripsi</p>',
    ])->assertRedirect(route('admin.services.edit', $service->fresh()));

    expect($service->fresh())
        ->name->toBe('Website Baru')
        ->slug->toBe('website-baru')
        ->image->toBe($media->path);
});

test('a service slug must be unique and the image must exist in the library', function () {
    Service::factory()->create(['slug' => 'dipakai']);
    $service = Service::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.services.update', $service), [
        'name' => 'X',
        'slug' => 'dipakai',
        'image' => 'media/tidak-ada.webp',
    ])->assertSessionHasErrors(['slug', 'image']);
});

test('services cannot be created or deleted from the admin', function () {
    $service = Service::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->post('/admin/services', [])->assertStatus(405);
    $this->actingAs($user)->delete(route('admin.services.update', $service))->assertStatus(405);
});

test('a service slug is generated from its name', function () {
    expect(Service::factory()->create(['name' => 'Desain Grafis'])->slug)->toBe('desain-grafis');
});
