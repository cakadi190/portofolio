<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Support\AppLocale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the site owner that a visitor sent a message through the contact form.
 */
class ContactMessageReceived extends Mailable
{
    use SerializesModels;

    public function __construct(public ContactMessage $contactMessage)
    {
        // The site owner reads these notifications in Indonesian whatever language the visitor used.
        $this->locale(AppLocale::DEFAULT);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->contactMessage->email],
            subject: "[Kontak] {$this->contactMessage->reason->label()} dari {$this->contactMessage->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message-received',
            text: 'mail.text.contact-message-received',
        );
    }
}
