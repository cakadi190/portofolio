<?php

use App\Enums\ContactReplyStatus;
use App\Mail\ContactMessageConfirmation;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\User;
use App\Services\EmailHtml;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\SentMessage;

/**
 * The mail sent through the in-memory array transport, as the recipient's client would receive it.
 */
function lastSentMail(): SentMessage
{
    return Mail::mailer('array')->getSymfonyTransport()->messages()->last();
}

test('submitting the contact form queues a receipt for the sender and a notification for the admin', function () {
    Mail::fake();

    $this->post(route('contact.store'), [
        'name' => 'Budi', 'email' => 'budi@example.com', 'reason' => 'general', 'message' => '<p>Halo admin</p>',
    ])->assertRedirect(route('contact.index'));

    $message = ContactMessage::firstOrFail();

    Mail::assertSent(ContactMessageConfirmation::class, fn ($mail) => $mail->hasTo('budi@example.com'));
    Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo(config('mail.from.address')) && $mail->hasReplyTo('budi@example.com') && $mail->contactMessage->is($message));
});

test('contact emails use the site layout, embed the icons and carry no raw html in the text part', function () {
    $this->post(route('contact.store'), [
        'name' => 'Budi', 'email' => 'budi@example.com', 'reason' => 'general',
        'message' => '<p>Halo <strong>admin</strong> &amp; tim</p><script>alert(1)</script>',
    ]);

    $sent = Mail::mailer('array')->getSymfonyTransport()->messages();
    expect($sent)->toHaveCount(2);

    foreach ($sent as $mail) {
        $email = $mail->getOriginalMessage();
        $html = $email->getHtmlBody();

        expect($html)
            ->toContain('Kunjungi Situs')
            ->toContain('class="hide-mobile"')
            ->toContain('cid:')
            ->not->toContain('<script')
            ->not->toContain('data:image');
        expect($email->getAttachments())->toHaveCount(2);
        expect($email->getTextBody())->toContain('Halo admin & tim')->not->toMatch('/<[a-z\/][^>]*>/i');
    }
});

test('the password reset mail uses the site layout', function () {
    $user = User::factory()->create(['name' => 'Amir']);

    $user->sendPasswordResetNotification('token-123');

    $email = lastSentMail()->getOriginalMessage();

    expect($email->getSubject())->toBe('Reset Password');
    expect($email->getHtmlBody())->toContain('Kunjungi Situs')->toContain('token-123')->toContain('Amir');
    expect($email->getTextBody())->toContain('token-123')->not->toMatch('/<[a-z\/][^>]*>/i');
});

test('an admin reply is emailed to the sender with a default subject and recorded as sent', function () {
    $message = ContactMessage::factory()->create(['email' => 'budi@example.com']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.contact-messages.reply', $message), [
        'subject' => '', 'body' => '<p>Terima kasih, <strong>siap</strong>.</p><script>x</script>',
    ])->assertRedirect(route('admin.contact-messages.index'));

    $reply = $message->replies()->firstOrFail();
    expect($reply)->subject->toBe('Membalas: Ingin Bertanya Hal Umum')->status->toBe(ContactReplyStatus::Sent)->user_id->toBe($admin->id);
    expect($reply->sent_at)->not->toBeNull();

    $email = lastSentMail()->getOriginalMessage();
    expect($email->getSubject())->toBe('Membalas: Ingin Bertanya Hal Umum');
    expect($email->getTo()[0]->getAddress())->toBe('budi@example.com');
    expect($email->getHtmlBody())->toContain('<strong>siap</strong>')->not->toContain('<script');
});

test('an admin reply can carry a custom subject and media library attachments', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/brosur.pdf', 'pdf-bytes');
    $media = Media::factory()->pdf()->create(['path' => 'media/brosur.pdf', 'name' => 'Brosur.pdf']);
    $message = ContactMessage::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.contact-messages.reply', $message), [
        'subject' => 'Brosur layanan', 'body' => '<p>Terlampir.</p>', 'attachments' => [$media->path],
    ])->assertRedirect();

    $email = lastSentMail()->getOriginalMessage();
    expect($email->getSubject())->toBe('Brosur layanan');
    expect(collect($email->getAttachments())->map(fn ($part) => $part->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'filename')))->toContain('Brosur.pdf');
    expect($email->getHtmlBody())->toContain('Brosur.pdf');
    expect($message->replies()->first()->attachments)->toBe(['media/brosur.pdf']);
});

test('a reply that cannot be delivered is recorded as failed', function () {
    $media = Media::factory()->pdf()->create(['path' => 'media/hilang.pdf']);
    $message = ContactMessage::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.contact-messages.reply', $message), [
        'body' => '<p>Halo</p>', 'attachments' => [$media->path],
    ])->assertRedirect();

    expect($message->replies()->firstOrFail())->status->toBe(ContactReplyStatus::Failed)->error->not->toBeEmpty();
});

test('a reply needs a body and only existing media as attachments', function () {
    $message = ContactMessage::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.contact-messages.reply', $message), [
        'body' => '<p> </p>', 'attachments' => ['media/tidak-ada.pdf'],
    ])->assertSessionHasErrors(['body', 'attachments.0']);

    expect($message->replies()->count())->toBe(0);
});

test('only admins can reply to messages', function () {
    $message = ContactMessage::factory()->create();

    $this->post(route('admin.contact-messages.reply', $message), ['body' => '<p>Halo</p>'])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->redaktur()->create())
        ->post(route('admin.contact-messages.reply', $message), ['body' => '<p>Halo</p>'])->assertForbidden();
});

test('email paragraphs are spaced except the last one of a block', function () {
    $html = EmailHtml::format('<p>Satu</p><p>Dua</p><p style="text-align: center">Tiga</p><ul><li>a</li></ul>');

    expect($html)->toContain('<p style="margin:0 0 16px">Satu</p>')
        ->toContain('<p style="margin:0 0 16px">Dua</p>')
        ->toContain('<p style="margin:0 0 16px; text-align: center">Tiga</p>')
        ->toContain('<ul style="margin:0">')
        ->and(EmailHtml::format('<script>x</script>'))->toBe('');
});

test('quoted messages are rounded without a side border and every email uses Signika', function () {
    $this->post(route('contact.store'), [
        'name' => 'Budi', 'email' => 'budi@example.com', 'reason' => 'general', 'message' => '<p>Halo</p><p>Dunia</p>',
    ]);

    foreach (Mail::mailer('array')->getSymfonyTransport()->messages() as $mail) {
        $html = $mail->getOriginalMessage()->getHtmlBody();

        expect($html)->toContain('border-radius:12px')
            ->not->toContain('border-left:3px solid #22c55e')
            ->toContain('family=Signika')
            ->toContain("font-family:'Signika'")
            ->not->toContain('<p style="margin:0 0 16px">Dunia</p>')
            ->toContain('<p style="margin:0">Dunia</p>');
    }
});

test('a reply asks the reader to open the attachment only when files are attached', function () {
    $message = ContactMessage::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.contact-messages.reply', $message), ['body' => '<p>Halo</p>'])->assertRedirect();
    $plain = lastSentMail()->getOriginalMessage();
    expect($plain->getHtmlBody())->not->toContain('membuka berkas lampiran')
        ->and($plain->getTextBody())->not->toContain('membuka berkas lampiran');

    Storage::fake('public');
    Storage::disk('public')->put('media/brosur.pdf', 'pdf-bytes');
    $media = Media::factory()->pdf()->create(['path' => 'media/brosur.pdf', 'name' => 'Brosur.pdf']);

    $this->actingAs($admin)->post(route('admin.contact-messages.reply', $message), ['body' => '<p>Halo</p>', 'attachments' => [$media->path]])->assertRedirect();
    $withFile = lastSentMail()->getOriginalMessage();
    expect($withFile->getHtmlBody())->toContain('Mohon berkenan membuka berkas lampiran yang kami kirimkan')
        ->and($withFile->getTextBody())->toContain('membuka berkas lampiran yang kami kirimkan');
});

test('every email carries the confidentiality notice in its own language only', function (string $locale, string $shown, string $hidden) {
    $this->post('/'.$locale.'/contact', [
        'name' => 'Budi', 'email' => 'budi@example.com', 'reason' => 'general', 'message' => '<p>Halo</p>',
    ]);

    $confirmation = collect(Mail::mailer('array')->getSymfonyTransport()->messages())
        ->map(fn ($mail) => $mail->getOriginalMessage())
        ->first(fn ($email) => in_array('budi@example.com', array_map(fn ($a) => $a->getAddress(), $email->getTo()), true));

    foreach ([$confirmation->getHtmlBody(), $confirmation->getTextBody()] as $body) {
        expect($body)
            ->toContain($shown)
            ->not->toContain($hidden)
            ->toContain('PT Kodingin Digital Nusantara (Kodinus Group)');
    }
})->with([
    'english' => ['en', 'CONFIDENTIAL: This email and any files', 'RAHASIA: Email ini'],
    'indonesian' => ['id', 'RAHASIA: Email ini beserta seluruh berkas', 'CONFIDENTIAL: This email'],
]);

test('the email footer is tinted apart from the page background', function () {
    $this->post(route('contact.store'), [
        'name' => 'Budi', 'email' => 'budi@example.com', 'reason' => 'general', 'message' => '<p>Halo</p>',
    ]);

    foreach (Mail::mailer('array')->getSymfonyTransport()->messages() as $mail) {
        expect($mail->getOriginalMessage()->getHtmlBody())
            ->toContain('background:#f6f9fc;border-top:1px solid #e8eef4');
    }
});
