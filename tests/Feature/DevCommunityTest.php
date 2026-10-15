<?php

use App\Enums\CommunityPlatform;
use App\Models\DevCommunity;
use App\Models\User;

test('the public page lists communities and filters by platform', function () {
    DevCommunity::factory()->create(['name' => 'Grup Discord', 'platform' => CommunityPlatform::Discord]);
    DevCommunity::factory()->create(['name' => 'Grup Telegram', 'platform' => CommunityPlatform::Telegram]);

    $this->get(route('resources.dev-communities.index', ['platform' => 'discord']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sumber-daya/komunitas-dev-id')
            ->has('communities.data', 1)
            ->where('communities.data.0.name', 'Grup Discord'));
});

test('an admin manages communities', function () {
    $admin = User::factory()->admin()->create();
    $payload = [
        'name' => 'Komunitas Uji',
        'platform' => 'whatsapp',
        'invite_url' => 'https://chat.whatsapp.com/abc',
        'member_count' => 10,
        'is_featured' => '1',
    ];

    $this->actingAs($admin)->post(route('admin.dev-communities.store'), $payload)
        ->assertRedirect(route('admin.dev-communities.index'));

    $community = DevCommunity::sole();
    expect($community->is_featured)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.dev-communities.update', $community), [...$payload, 'name' => 'Baru'])
        ->assertRedirect();
    expect($community->refresh()->name)->toBe('Baru');

    $this->actingAs($admin)->delete(route('admin.dev-communities.destroy', $community))->assertRedirect();
    expect(DevCommunity::count())->toBe(0);
});

test('a community needs a valid platform and an http link', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.dev-communities.store'), ['name' => 'X', 'platform' => 'fax', 'invite_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors(['platform', 'invite_url']);
});

test('only admins can open the community admin', function () {
    $this->actingAs(User::factory()->redaktur()->create())
        ->get(route('admin.dev-communities.index'))
        ->assertForbidden();
});
