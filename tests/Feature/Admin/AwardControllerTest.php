<?php

use App\Enums\AwardType;
use App\Models\Award;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.awards.index'))->assertRedirect(route('login'));
});

test('an award can be created', function () {
    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.awards.store'), [
        'event_name' => 'Hackathon 2026',
        'title' => 'Juara 1',
        'type' => 'competition',
        'year' => 2026,
        'rank' => 1,
    ]);

    $response->assertRedirect(route('admin.awards.index'));
    $this->assertDatabaseHas('awards', ['title' => 'Juara 1', 'rank' => 1]);
});

test('creating an award requires a title and year', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.awards.store'), ['event_name' => 'Hackathon']);

    $response->assertSessionHasErrors(['title', 'type', 'year']);
});

test('an award can be updated', function () {
    $award = Award::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())->put(route('admin.awards.update', $award), [
        'event_name' => $award->event_name,
        'title' => 'Judul Baru',
        'type' => 'honors',
        'year' => $award->year,
    ]);

    $response->assertRedirect(route('admin.awards.index'));
    expect($award->refresh()->title)->toBe('Judul Baru')
        ->and($award->type)->toBe(AwardType::Honors);
});

test('an award can be deleted', function () {
    $award = Award::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.awards.destroy', $award));

    $response->assertRedirect(route('admin.awards.index'));
    $this->assertDatabaseMissing('awards', ['id' => $award->id]);
});

test('the icon is derived automatically from rank and title', function (?int $rank, string $title, string $icon) {
    expect(Award::factory()->make(['rank' => $rank, 'title' => $title])->icon)->toBe($icon);
})->with([
    'podium place' => [2, 'Web Design Competition', 'fa6-solid:trophy'],
    'lower place' => [5, 'Web Design Competition', 'fa6-solid:medal'],
    'unranked' => [null, 'Junior Web Developer', 'mdi:certificate'],
    'certification title' => [1, 'Sertifikasi BNSP', 'mdi:certificate'],
]);

test('an award rejects an unknown type', function () {
    $response = $this->actingAs(User::factory()->admin()->create())->post(route('admin.awards.store'), [
        'event_name' => 'Hackathon',
        'title' => 'Juara 1',
        'type' => 'bogus',
        'year' => 2026,
    ]);

    $response->assertSessionHasErrors('type');
});

test('the certification type always uses the certificate icon', function () {
    expect(Award::factory()->make(['rank' => 1, 'title' => 'AWS', 'type' => AwardType::Certification])->icon)
        ->toBe('mdi:certificate');
});
