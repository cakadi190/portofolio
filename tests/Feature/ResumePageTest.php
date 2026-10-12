<?php

use App\Enums\ResumeProfile;
use App\Models\Award;
use App\Models\Career;
use App\Models\Portfolio;
use App\Models\Technology;
use App\Services\ResumeService;
use Barryvdh\DomPDF\Facade\Pdf;

test('the resume page renders the default profile pointing at the generated pdf', function () {
    $this->get(route('resume.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('resume/index')
            ->where('profile', 'technical')
            ->where('pdfUrl', route('resume.pdf'))
            ->where('downloadName', 'CV-Amir-Zuhdi-Wibowo-Technical.pdf')
            ->has('profiles', 2));
});

test('each profile has its own shareable url, pdf target and descriptive filename', function () {
    $this->get('/resume/executive')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('profile', 'executive')
            ->where('pdfUrl', route('resume.profile.pdf', ResumeProfile::Executive))
            ->where('downloadName', 'CV-Amir-Zuhdi-Wibowo-Executive-Business.pdf')
            ->where('profiles.0.url', route('resume.profile', ResumeProfile::Technical))
            ->where('profiles.1.pdfUrl', route('resume.profile.pdf', ResumeProfile::Executive)));
});

test('an unknown profile is not found', function () {
    $this->get('/resume/unknown')->assertNotFound();
    $this->get('/resume/unknown.pdf')->assertNotFound();
});

test('the resume pdf is public and served inline', function () {
    Career::create(['position' => 'Backend Engineer', 'company' => 'Acme', 'location' => 'Ngawi', 'start_date' => '2020-01-01', 'end_date' => null]);

    $response = $this->get(route('resume.pdf'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Robots-Tag', 'noindex');

    expect($response->getContent())->toStartWith('%PDF')->toEndWith("%%EOF\n");
    expect($response->headers->get('Content-Disposition'))->toContain('CV-Amir-Zuhdi-Wibowo-Technical.pdf');
});

test('the executive pdf downloads under its own filename', function () {
    $response = $this->get('/resume/executive.pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('CV-Amir-Zuhdi-Wibowo-Executive-Business.pdf');
});

test('a failed pdf generation answers with a service unavailable error', function () {
    Pdf::shouldReceive('loadView')->andThrow(new RuntimeException('dompdf exploded'));

    $this->get(route('resume.pdf'))->assertStatus(503);
});

test('the resume view lists profile data and omits private portfolios', function () {
    Career::create(['position' => 'Backend Engineer', 'company' => 'Acme', 'location' => 'Ngawi', 'start_date' => '2020-01-01', 'end_date' => null]);
    Award::factory()->create(['title' => 'Juara Hackathon']);
    Portfolio::factory()->create(['name' => 'Proyek Rahasia', 'is_private' => true]);

    $html = view('resume', app(ResumeService::class)->build(ResumeProfile::Technical))->render();

    expect($html)->toContain('Backend Engineer', 'Acme', 'Juara Hackathon')->not->toContain('Proyek Rahasia');
});

test('profiles share data but select, order and limit sections differently', function () {
    Career::create(['position' => 'Backend Engineer', 'company' => 'Acme', 'location' => 'Ngawi', 'start_date' => '2020-01-01', 'end_date' => null]);
    Portfolio::factory()->count(5)->create(['is_private' => false])->each(
        fn (Portfolio $portfolio) => $portfolio->technologies()->attach(Technology::factory()->create(['name' => "Tech {$portfolio->id}"])),
    );

    $service = app(ResumeService::class);
    $technical = $service->build(ResumeProfile::Technical);
    $executive = $service->build(ResumeProfile::Executive);
    $keys = fn (array $resume): array => array_column($resume['sections'], 'key');
    $projects = fn (array $resume): array => collect($resume['sections'])->firstWhere('key', 'projects')['entries'];

    expect($keys($technical))->toContain('skills')->and($keys($executive))->not->toContain('skills')
        ->and(array_search('projects', $keys($technical)))->toBeLessThan(array_search('career', $keys($technical)))
        ->and(array_search('career', $keys($executive)))->toBeLessThan(array_search('projects', $keys($executive)))
        ->and($projects($technical))->toHaveCount(5)
        ->and($projects($executive))->toHaveCount(3)
        ->and($projects($technical)[0]['chips'])->not->toBeEmpty()
        ->and($projects($executive)[0]['chips'])->toBeEmpty()
        ->and($technical['headline'])->not->toBe($executive['headline']);
});

test('sections without data are omitted instead of breaking the document', function () {
    $resume = app(ResumeService::class)->build(ResumeProfile::Executive);

    expect(array_column($resume['sections'], 'key'))->toBe(['summary'])
        ->and(fn () => view('resume', $resume)->render())->not->toThrow(Throwable::class);
});
