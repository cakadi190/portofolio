<?php

namespace App\Jobs;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the site owner about a new contact message, with sender details and content.
 */
class SendContactAdminNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    public function handle(SystemSettingService $systemSettings): void
    {
        try {
            Mail::to($systemSettings->get(SystemSetting::CONTACT_RECIPIENT_EMAIL, config('mail.from.address')))
                ->send(new ContactMessageReceived($this->contactMessage));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
