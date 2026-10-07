<?php

use App\Models\ContactMessage;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.contact-messages.index'))->assertRedirect(route('login'));
});

test('authenticated users can list messages', function () {
    ContactMessage::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/contact-messages/index')
            ->has('messages.data', 3)
            ->where('messages.data.0.reason_label', 'Ingin Bertanya Hal Umum'));
});

test('a message read state can be toggled', function () {
    $message = ContactMessage::factory()->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->put(route('admin.contact-messages.update', $message));
    expect($message->refresh()->read_at)->not->toBeNull();

    $this->actingAs($user)->put(route('admin.contact-messages.update', $message));
    expect($message->refresh()->read_at)->toBeNull();
});

test('a message can be deleted', function () {
    $message = ContactMessage::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.contact-messages.destroy', $message))
        ->assertRedirect(route('admin.contact-messages.index'));

    $this->assertModelMissing($message);
});
