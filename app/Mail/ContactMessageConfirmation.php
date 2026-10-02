<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirms to the visitor that their contact form message will be processed.
 */
class ContactMessageConfirmation extends Mailable
{
    use SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pesan Anda sudah kami terima');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-message-confirmation');
    }
}
