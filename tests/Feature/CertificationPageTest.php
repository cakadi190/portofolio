<?php

use App\Models\Certification;

test('a pdf certification opens in the dedicated viewer page with its details', function () {
    $certification = Certification::factory()->create([
        'title' => 'AWS Developer',
        'issuer' => 'Amazon',
        'issued_at' => '2025-03-10',
        'credential_id' => 'AWS-123',
        'credential_url' => 'https://verify.example.com/aws-123',
        'file' => 'certifications/aws.pdf',
    ]);

    $this->get(route('certifications.show', $certification->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('certification/show')
            ->where('title', 'AWS Developer')
            ->where('pdfUrl', '/storage/certifications/aws.pdf')
            ->where('downloadName', 'aws-developer.pdf')
            ->where('info.0', ['label' => 'Nama sertifikat', 'value' => 'AWS Developer'])
            ->where('info.1', ['label' => 'Penerbit', 'value' => 'Amazon'])
            ->where('info.3.value', 'AWS-123')
            ->where('info.4.href', 'https://verify.example.com/aws-123'));
});

test('the slug resolves by id even when the title part is stale', function () {
    $certification = Certification::factory()->create(['title' => 'Old Title']);

    expect($certification->slug)->toBe($certification->id.'-old-title');

    $this->get('/certifications/'.$certification->id.'-renamed')->assertOk();
});

test('an image certification opens a plain detail page', function () {
    $image = Certification::factory()->create(['title' => 'Badge', 'file' => 'certifications/badge.png']);

    $this->get(route('certifications.show', $image->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('certification/detail')
            ->where('imageUrl', '/storage/certifications/badge.png')
            ->where('info.0', ['label' => 'Nama sertifikat', 'value' => 'Badge']));
});

test('unknown slugs are not found', function () {
    $this->get('/certifications/999')->assertNotFound();
    $this->get('/certifications/not-a-slug')->assertNotFound();
});

test('the legacy certification urls redirect permanently', function () {
    $this->get('/certification/5-foo')->assertRedirect('/certifications/5-foo')->assertStatus(301);
    $this->get('/certificates/5-foo')->assertRedirect('/certifications/5-foo')->assertStatus(301);
});

test('the about page links every certification to its page', function () {
    $pdf = Certification::factory()->create(['file' => 'certifications/a.pdf']);
    $image = Certification::factory()->create(['file' => 'certifications/b.png']);

    $this->get(route('about.me'))
        ->assertInertia(fn ($page) => $page
            ->where('certifications', fn ($items) => $items->firstWhere('id', $pdf->id)['pageUrl'] === route('certifications.show', $pdf->slug)
                && $items->firstWhere('id', $image->id)['pageUrl'] === route('certifications.show', $image->slug)));
});
