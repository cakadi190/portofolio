<?php

use App\Enums\ContactReason;

test('the disclaimer page is public', function () {
    $this->get(route('disclaimer.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('disclaimer/index'));
});

test('the disclaimer page is listed in the pages sitemap', function () {
    expect($this->get(route('sitemaps.pages'))->getContent())->toContain('/disclaimer');
});

test('a correction request can be sent through the contact form', function () {
    $this->post(route('contact.store'), [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'reason' => ContactReason::Correction->value,
        'message' => '<p>Ada kekeliruan pada artikel.</p>',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('contact_messages', ['reason' => 'correction']);
});
