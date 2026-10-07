<?php

use App\Enums\EducationLevel;
use App\Models\User;

function validEducationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Politeknik Negeri Madiun',
        'level' => 'university',
        'start_date' => '2022-07-12',
        'place' => 'Madiun',
    ], $overrides);
}

test('an education can be created with a valid level and score type', function () {
    $response = $this->actingAs(User::factory()->admin()->create())->post(
        route('admin.educations.store'),
        validEducationPayload(['academic_score_type' => 'gpa']),
    );

    $response->assertRedirect(route('admin.educations.index'));
    $this->assertDatabaseHas('educations', ['name' => 'Politeknik Negeri Madiun', 'level' => 'university', 'academic_score_type' => 'gpa']);
});

test('an education rejects levels and score types outside the enum', function () {
    $response = $this->actingAs(User::factory()->admin()->create())->post(
        route('admin.educations.store'),
        validEducationPayload(['level' => 'doctorate', 'academic_score_type' => 'other']),
    );

    $response->assertSessionHasErrors(['level', 'academic_score_type']);
});

test('the index exposes level options', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.educations.index'))
        ->assertInertia(fn ($page) => $page->has('levels', count(EducationLevel::cases())));
});
