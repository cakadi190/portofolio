<?php

namespace App\Mail;

use App\Models\ContactMessageReply;
use App\Models\Media;
use App\Services\MediaStorage;
use App\Support\AppLocale;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * The admin's reply to a visitor's contact message, with optional media library attachments.
 */
class ContactReplyEmail extends Mailable
{
    use SerializesModels;

    public function __construct(public ContactMessageReply $reply, public ?string $replyToAddress = null)
    {
        $this->locale($reply->contactMessage->locale ?? AppLocale::DEFAULT);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: $this->replyToAddress ? [new Address($this->replyToAddress)] : [],
            subject: $this->reply->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message-reply',
            text: 'mail.text.contact-message-reply',
            with: ['attachmentNames' => $this->attachmentNames()],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return $this->media()->map(fn (Media $media): Attachment => Attachment::fromData(fn (): string => $this->contents($media), $media->name)
            ->withMime($media->mime_type))->values()->all();
    }

    /**
     * @throws FileNotFoundException When the file is gone from its disk.
     */
    private function contents(Media $media): string
    {
        $disk = app(MediaStorage::class)->filesystem($media->disk);

        if (! $disk->exists($media->path)) {
            throw new FileNotFoundException("Attachment [{$media->path}] was not found on disk [{$media->disk}].");
        }

        return (string) $disk->get($media->path);
    }

    /**
     * @return list<string>
     */
    private function attachmentNames(): array
    {
        return $this->media()->map(fn (Media $media): string => $media->name)->values()->all();
    }

    /**
     * @return Collection<int, Media>
     */
    private function media(): Collection
    {
        return Media::query()->whereIn('path', $this->reply->attachments ?? [])->get();
    }
}
