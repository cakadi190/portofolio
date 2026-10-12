<?php

use App\Models\Activity;
use App\Models\User;

beforeEach(function () {
    Activity::query()->delete();
});

function makeActivity(array $attributes = []): Activity
{
    return Activity::query()->create($attributes + [
        'log_name' => 'content', 'event' => 'created', 'description' => 'Deskripsi', 'properties' => [], 'attribute_changes' => [],
    ]);
}

test('guests, redaktur and users cannot see the log or export it', function () {
    $this->get(route('admin.activity-logs.index'))->assertRedirect(route('login'));

    foreach ([User::factory()->redaktur()->create(), User::factory()->create()] as $user) {
        $this->actingAs($user)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.activity-logs.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.activity-logs.show', makeActivity()))->assertForbidden();
    }
});

test('filters combine and survive pagination', function () {
    makeActivity(['description' => 'Alpha post', 'log_name' => 'content', 'ip_address' => '203.0.113.5']);
    makeActivity(['description' => 'Alpha login', 'log_name' => 'auth', 'event' => 'login', 'ip_address' => '203.0.113.5']);
    makeActivity(['description' => 'Beta', 'log_name' => 'auth', 'event' => 'login', 'outcome' => 'failure']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.activity-logs.index', ['search' => 'Alpha', 'log_name' => 'auth', 'ip_address' => '203.0.113.5', 'per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/activity-logs/index')
            ->has('activities.data', 1)
            ->where('activities.data.0.description', 'Alpha login'));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.activity-logs.index', ['outcome' => 'failure']))
        ->assertInertia(fn ($page) => $page->has('activities.data', 1));
});

test('invalid filters are rejected and the page size is bounded', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.activity-logs.index', ['per_page' => 100000]))->assertSessionHasErrors('per_page');
    $this->actingAs($admin)->get(route('admin.activity-logs.index', ['sort' => 'properties; drop']))->assertSessionHasErrors('sort');
});

test('results are paginated with a deterministic default order', function () {
    $admin = User::factory()->admin()->create();
    Activity::query()->delete();

    foreach (range(1, 30) as $i) {
        makeActivity(['description' => "row {$i}", 'created_at' => now()]);
    }

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'))
        ->assertInertia(fn ($page) => $page->has('activities.data', 25)->where('activities.total', 30)->where('activities.data.0.description', 'row 30'));
});

test('the detail view shows sanitized properties and tolerates a deleted subject', function () {
    $activity = makeActivity(['subject_type' => 'App\\Models\\Tag', 'subject_id' => 999, 'properties' => ['token' => 'leak-me', 'safe' => 1]]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.activity-logs.show', $activity))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('activity.subject_exists', false)
            ->where('activity.properties.token', '[REDACTED]')
            ->where('activity.properties.safe', 1));
});

test('exports are bounded, neutralise formulas and are themselves audited', function () {
    makeActivity(['description' => '=HYPERLINK("http://evil")']);

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.activity-logs.export'));
    $response->assertOk();

    expect($response->streamedContent())->toContain("'=HYPERLINK")
        ->and(Activity::query()->where('event', 'activity_exported')->exists())->toBeTrue();
});
