<?php

namespace App\Jobs;

use App\Mail\ContactMessageConfirmation;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the "message received" receipt to the visitor who used the contact form.
 */
class SendContactReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    public function handle(): void
    {
        try {
            Mail::to($this->contactMessage->email)->send(new ContactMessageConfirmation($this->contactMessage));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
