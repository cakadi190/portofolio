<?php

use App\Mail\ContactMessageConfirmation;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;

function validContactMessage(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'reason' => 'project_collaboration',
        'message' => '<p>Halo, <strong>ayo kolaborasi</strong></p>',
    ], $overrides);
}

test('the contact page lists only filled active settings with derived links', function () {
    SystemSetting::factory()->create(['key' => 'contact_email', 'value' => 'halo@example.com']);
    SystemSetting::factory()->create(['key' => 'contact_whatsapp', 'value' => '081234771365']);
    SystemSetting::factory()->create(['key' => 'contact_website', 'value' => null]);
    SystemSetting::factory()->create(['key' => 'contact_address', 'value' => 'Ngawi', 'is_active' => false]);
    SystemSetting::factory()->create(['key' => 'social_instagram', 'value' => 'https://instagram.com/cakadi']);
    SystemSetting::factory()->contactRecipient()->create();

    $this->get(route('contact.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('contact/index')
            ->has('information', 2)
            ->where('information.0.url', 'mailto:halo@example.com')
            ->where('information.1.url', 'https://wa.me/6281234771365')
            ->has('socials', 1)
            ->where('socials.0.label', 'Instagram')
            ->has('reasons', 4));
});

test('a visitor can send a message', function () {
    Mail::fake();

    $this->post(route('contact.store'), validContactMessage())
        ->assertRedirect(route('contact.index'));

    $message = ContactMessage::query()->firstOrFail();

    expect($message->read_at)->toBeNull()->and($message->reason->value)->toBe('project_collaboration');
});

test('a message is emailed to the configured recipient and confirmed to the sender', function () {
    Mail::fake();
    SystemSetting::factory()->contactRecipient('inbox@example.com')->create();

    $this->post(route('contact.store'), validContactMessage());

    Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('inbox@example.com') && $mail->hasReplyTo('budi@example.com'));
    Mail::assertSent(ContactMessageConfirmation::class, fn ($mail) => $mail->hasTo('budi@example.com'));
});

test('the recipient falls back to the sender address when no setting exists', function () {
    Mail::fake();
    config(['mail.from.address' => 'fallback@example.com']);

    $this->post(route('contact.store'), validContactMessage());

    Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('fallback@example.com'));
});

test('the message is kept when sending email fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    $this->post(route('contact.store'), validContactMessage())->assertRedirect(route('contact.index'));

    expect(ContactMessage::query()->count())->toBe(1);
});

test('required fields are validated', function () {
    $this->post(route('contact.store'), ['message' => '<p></p>', 'reason' => 'x'])
        ->assertSessionHasErrors(['name', 'email', 'reason', 'message']);

    expect(ContactMessage::query()->count())->toBe(0);
});

test('unsafe html is stripped from the message', function () {
    $this->post(route('contact.store'), validContactMessage([
        'message' => '<p onclick="x()">Hai</p><script>alert(1)</script>',
    ]));

    expect(ContactMessage::query()->firstOrFail()->message)->toBe('<p>Hai</p>alert(1)');
});
