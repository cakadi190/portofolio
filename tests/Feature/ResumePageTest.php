<?php

use App\Http\Controllers\ResumeController;
use App\Models\Award;
use App\Models\Career;
use App\Models\Portfolio;

test('the resume page renders the viewer pointing at the generated pdf', function () {
    $this->get(route('resume.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('resume/index')->where('pdfUrl', route('resume.pdf')));
});

test('the resume pdf is public and served inline', function () {
    Career::create(['position' => 'Backend Engineer', 'company' => 'Acme', 'location' => 'Ngawi', 'start_date' => '2020-01-01', 'end_date' => null]);

    $response = $this->get(route('resume.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Robots-Tag', 'noindex');

    expect($response->getContent())->toStartWith('%PDF')->toEndWith("%%EOF\n");
});

test('the resume view lists profile data and omits private portfolios', function () {
    Career::create(['position' => 'Backend Engineer', 'company' => 'Acme', 'location' => 'Ngawi', 'start_date' => '2020-01-01', 'end_date' => null]);
    Award::factory()->create(['title' => 'Juara Hackathon']);
    Portfolio::factory()->create(['name' => 'Proyek Rahasia', 'is_private' => true]);

    $html = view('resume', (new ReflectionMethod(ResumeController::class, 'resume'))->invoke(app(ResumeController::class)))->render();

    expect($html)->toContain('Backend Engineer', 'Acme', 'Juara Hackathon')->not->toContain('Proyek Rahasia');
});
