<?php

namespace App\Models;

use App\Enums\ContactReplyStatus;
use Database\Factories\ContactMessageReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An admin's emailed answer to a contact message, with its delivery result.
 *
 * @property int $id
 * @property int $contact_message_id
 * @property int|null $user_id
 * @property string $subject
 * @property string $body
 * @property list<string>|null $attachments Media library paths.
 * @property ContactReplyStatus $status
 * @property string|null $error
 * @property Carbon|null $sent_at
 */
class ContactMessageReply extends Model
{
    /** @use HasFactory<ContactMessageReplyFactory> */
    use HasFactory;

    protected $fillable = ['contact_message_id', 'user_id', 'subject', 'body', 'attachments', 'status', 'error', 'sent_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'status' => ContactReplyStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContactMessage, $this>
     */
    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
