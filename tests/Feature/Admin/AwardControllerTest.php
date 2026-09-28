<?php

use App\Models\Award;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.awards.index'))->assertRedirect(route('login'));
});

test('an award can be created with an icon upload', function () {
    $response = $this->actingAs(User::factory()->create())->post(route('admin.awards.store'), [
        'event_name' => 'Hackathon 2026',
        'title' => 'Juara 1',
        'icon' => UploadedFile::fake()->image('icon.png'),
        'year' => 2026,
        'rank' => 1,
    ]);

    $response->assertRedirect(route('admin.awards.index'));

    $award = Award::query()->firstOrFail();
    expect($award->icon)->not->toBeNull();
    Storage::disk('public')->assertExists($award->icon);
});

test('creating an award requires a title and year', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.awards.store'), ['event_name' => 'Hackathon']);

    $response->assertSessionHasErrors(['title', 'year']);
});

test('updating an award replaces the previous icon', function () {
    $award = Award::factory()->create(['icon' => 'awards/old.png']);
    Storage::disk('public')->put('awards/old.png', 'fake-content');

    $response = $this->actingAs(User::factory()->create())->put(route('admin.awards.update', $award), [
        'event_name' => $award->event_name,
        'title' => $award->title,
        'icon' => UploadedFile::fake()->image('new.png'),
        'year' => $award->year,
    ]);

    $response->assertRedirect(route('admin.awards.index'));

    $award->refresh();
    Storage::disk('public')->assertMissing('awards/old.png');
    Storage::disk('public')->assertExists($award->icon);
});

test('an award can be deleted along with its icon', function () {
    $award = Award::factory()->create(['icon' => 'awards/icon.png']);
    Storage::disk('public')->put('awards/icon.png', 'fake-content');

    $response = $this->actingAs(User::factory()->create())
        ->delete(route('admin.awards.destroy', $award));

    $response->assertRedirect(route('admin.awards.index'));
    $this->assertDatabaseMissing('awards', ['id' => $award->id]);
    Storage::disk('public')->assertMissing('awards/icon.png');
});
