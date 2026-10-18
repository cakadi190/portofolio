<?php

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
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

test('the list shows each reply with its translated status label', function () {
    $message = ContactMessage::factory()->create();
    ContactMessageReply::factory()->for($message, 'contactMessage')->sent()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('messages.data.0.replies.0.status', 'sent')
            ->where('messages.data.0.replies.0.status_label', 'Terkirim'));
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

test('the list tells the admin which language the sender used and defaults the subject to it', function () {
    ContactMessage::factory()->create(['locale' => 'en', 'created_at' => now()->subDay()]);
    ContactMessage::factory()->create(['locale' => null, 'created_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.contact-messages.index'))
        ->assertInertia(fn ($page) => $page
            ->has('messages.data', 2)
            ->where('messages.data.0.locale', 'id')
            ->where('messages.data.0.locale_label', 'Bahasa Indonesia')
            ->where('messages.data.1.locale', 'en')
            ->where('messages.data.1.default_subject', 'Re: General Question'));
});
