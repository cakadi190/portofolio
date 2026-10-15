<?php

use App\Enums\ChannelPlatform;
use App\Models\ProgrammerChannel;
use App\Models\User;

test('the public page lists channels and filters by platform', function () {
    ProgrammerChannel::factory()->create(['name' => 'Channel YouTube', 'platform' => ChannelPlatform::YouTube]);
    ProgrammerChannel::factory()->create(['name' => 'Channel Telegram', 'platform' => ChannelPlatform::Telegram]);

    $this->get(route('resources.programmer-channels.index', ['platform' => 'youtube']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('sumber-daya/channel-programmer')
            ->has('channels.data', 1)
            ->where('channels.data.0.name', 'Channel YouTube'));
});

test('an admin manages channels', function () {
    $admin = User::factory()->admin()->create();
    $payload = [
        'name' => 'Channel Uji',
        'platform' => 'youtube',
        'url' => 'https://youtube.com/@abc',
        'is_featured' => '1',
    ];

    $this->actingAs($admin)->post(route('admin.programmer-channels.store'), $payload)
        ->assertRedirect(route('admin.programmer-channels.index'));

    $channel = ProgrammerChannel::sole();
    expect($channel->is_featured)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.programmer-channels.update', $channel), [...$payload, 'name' => 'Baru'])
        ->assertRedirect();
    expect($channel->refresh()->name)->toBe('Baru');

    $this->actingAs($admin)->delete(route('admin.programmer-channels.destroy', $channel))->assertRedirect();
    expect(ProgrammerChannel::count())->toBe(0);
});

test('a channel needs a valid platform and an http link', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.programmer-channels.store'), ['name' => 'X', 'platform' => 'fax', 'url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors(['platform', 'url']);
});

test('only admins can open the channel admin', function () {
    $this->actingAs(User::factory()->redaktur()->create())
        ->get(route('admin.programmer-channels.index'))
        ->assertForbidden();
});
