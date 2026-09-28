<?php

use App\Models\Career;
use App\Models\Portfolio;
use App\Models\User;

function careerPayload(array $overrides = []): array
{
    return array_merge([
        'position' => 'Fullstack Developer',
        'company' => 'PT Contoh',
        'location' => 'Jakarta',
        'start_date' => '2023-01-10',
    ], $overrides);
}

test('a career can be created with related portfolios', function () {
    $portfolios = Portfolio::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.careers.store'), careerPayload(['portfolios' => $portfolios->modelKeys()]))
        ->assertRedirect(route('admin.careers.index'));

    expect(Career::query()->firstOrFail()->portfolios)->toHaveCount(2);
});

test('the index serializes dates in a format date inputs accept', function () {
    Career::query()->create(careerPayload(['end_date' => '2024-02-20']));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.careers.index'))
        ->assertInertia(fn ($page) => $page
            ->where('careers.data.0.start_date', '2023-01-10')
            ->where('careers.data.0.end_date', '2024-02-20'));
});

test('a career can be updated and its portfolios resynced', function () {
    $career = Career::query()->create(careerPayload());
    $career->portfolios()->attach(Portfolio::factory()->create());

    $this->actingAs(User::factory()->create())
        ->put(route('admin.careers.update', $career), careerPayload(['position' => 'Tech Lead']))
        ->assertRedirect(route('admin.careers.index'));

    expect($career->refresh()->position)->toBe('Tech Lead')
        ->and($career->portfolios)->toHaveCount(0);
});

test('a career end date cannot precede its start date', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('admin.careers.store'), careerPayload(['end_date' => '2022-01-01']))
        ->assertSessionHasErrors('end_date');
});

test('a career can be deleted', function () {
    $career = Career::query()->create(careerPayload());

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.careers.destroy', $career))
        ->assertRedirect(route('admin.careers.index'));

    $this->assertModelMissing($career);
});
