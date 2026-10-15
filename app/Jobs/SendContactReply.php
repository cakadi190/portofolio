<?php

namespace App\Jobs;

use App\Enums\ContactReplyStatus;
use App\Mail\ContactReplyEmail;
use App\Models\ContactMessageReply;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Emails an admin's reply to the visitor and records whether delivery worked.
 */
class SendContactReply implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessageReply $reply) {}

    public function handle(SystemSettingService $systemSettings): void
    {
        try {
            $replyTo = $systemSettings->get(SystemSetting::CONTACT_RECIPIENT_EMAIL, config('mail.from.address'));

            Mail::to($this->reply->contactMessage->email)->send(new ContactReplyEmail($this->reply, $replyTo));

            $this->reply->update(['status' => ContactReplyStatus::Sent, 'sent_at' => now(), 'error' => null]);
        } catch (\Throwable $exception) {
            report($exception);

            $this->reply->update(['status' => ContactReplyStatus::Failed, 'error' => str($exception->getMessage())->limit(500)->toString()]);
        }
    }
}
