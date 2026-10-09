<?php

use App\Models\Media;
use App\Models\SpeakingEngagement;
use App\Models\User;
use App\Services\MediaService;

function speakingEngagementPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Building Digital Solutions in the AI Era',
        'organizer' => 'Diwostech.id',
        'role' => 'trainer',
        'format' => 'online',
        'location' => 'Zoom & YouTube',
        'starts_at' => '2026-10-11 19:30',
        'ends_at' => '2026-10-11 21:30',
        'registration_url' => 'https://bit.ly/Diwostech8',
        'is_published' => true,
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.speaking-engagements.index'))->assertRedirect(route('login'));
});

test('a speaking engagement can be created with a media library poster', function () {
    $poster = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.speaking-engagements.store'), speakingEngagementPayload(['poster' => $poster->path]))
        ->assertRedirect(route('admin.speaking-engagements.index'));

    $this->assertDatabaseHas('speaking_engagements', ['organizer' => 'Diwostech.id', 'role' => 'trainer', 'poster' => $poster->path]);
});

test('the poster is optional but must come from the media library when given', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('admin.speaking-engagements.store'), speakingEngagementPayload())
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('admin.speaking-engagements.store'), speakingEngagementPayload(['poster' => 'media/unknown.webp']))
        ->assertSessionHasErrors('poster');
});

test('creating a speaking engagement validates required fields, enums and dates', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('admin.speaking-engagements.store'), [])
        ->assertSessionHasErrors(['title', 'organizer', 'role', 'format', 'starts_at', 'is_published']);

    $this->actingAs($user)
        ->post(route('admin.speaking-engagements.store'), speakingEngagementPayload([
            'role' => 'wizard',
            'ends_at' => '2026-10-10 10:00',
            'registration_url' => 'not-a-url',
        ]))
        ->assertSessionHasErrors(['role', 'ends_at', 'registration_url']);
});

test('a speaking engagement can be updated', function () {
    $engagement = SpeakingEngagement::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.speaking-engagements.update', $engagement), speakingEngagementPayload(['title' => 'Judul Baru', 'is_published' => false]))
        ->assertRedirect(route('admin.speaking-engagements.index'));

    expect($engagement->refresh())->title->toBe('Judul Baru')->is_published->toBeFalse();
});

test('a speaking engagement can be deleted', function () {
    $engagement = SpeakingEngagement::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.speaking-engagements.destroy', $engagement))
        ->assertRedirect(route('admin.speaking-engagements.index'));

    $this->assertModelMissing($engagement);
});

test('the index lists engagements with role and format options', function () {
    SpeakingEngagement::factory()->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.speaking-engagements.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/speaking-engagements/index')
            ->has('speakingEngagements.data', 2)
            ->has('roles', 5)
            ->has('formats', 3));
});

test('a poster in use cannot be deleted from the media library', function () {
    $poster = Media::factory()->create();
    SpeakingEngagement::factory()->create(['poster' => $poster->path]);

    expect(app(MediaService::class)->usageCount($poster))->toBe(1);
});
