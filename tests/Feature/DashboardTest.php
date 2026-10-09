<?php

use App\Models\Post;
use App\Models\SpeakingEngagement;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('admins get the full summary including site data and system info', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/index')
            ->has('summary.blog')
            ->has('summary.site.counts')
            ->has('summary.system.php_version'));
});

test('redaktur only get blog figures', function () {
    $this->actingAs(User::factory()->redaktur()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('summary.blog')
            ->where('summary.site', null)
            ->where('summary.system', null));
});

test('calendar events list posts for staff and speaking engagements for admins only', function () {
    Post::factory()->create(['title' => 'Artikel Oktober', 'published_at' => '2026-10-05 08:00:00']);
    Post::factory()->create(['title' => 'Artikel September', 'published_at' => '2026-09-05 08:00:00']);
    SpeakingEngagement::factory()->create(['title' => 'Talk Oktober', 'starts_at' => '2026-10-10 19:30:00']);

    $this->actingAs(User::factory()->redaktur()->create())
        ->getJson(route('admin.calendar-events', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonStructure(['upcoming'])
        ->assertJsonPath('events.0.title', 'Artikel Oktober');

    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('admin.calendar-events', ['month' => '2026-10']))
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.1.title', 'Talk Oktober');

    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('admin.calendar-events', ['month' => 'bukan-bulan']))
        ->assertUnprocessable();

    $this->actingAs(User::factory()->create())
        ->getJson(route('admin.calendar-events'))
        ->assertForbidden();
});
