<?php

namespace App\Http\Controllers;

use App\Enums\ContactReason;
use App\Enums\SystemSettingGroup;
use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageConfirmation;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private SystemSettingService $systemSettings) {}

    /**
     * Show the contact page: managed contact details plus the message form.
     */
    public function index(): Response
    {
        return Inertia::render('contact/index', [
            'information' => $this->contactItems(SystemSettingGroup::Information),
            'socials' => $this->contactItems(SystemSettingGroup::SocialMedia),
            'reasons' => ContactReason::options(),
        ]);
    }

    /**
     * Build the filled-in contact entries of a group, skipping blank settings.
     *
     * @return list<array{label: string, value: string, url: string|null, note: string|null}>
     */
    private function contactItems(SystemSettingGroup $group): array
    {
        $items = [];

        foreach ($group->fields() as $field) {
            $value = $this->systemSettings->get($field['key']);

            if (blank($value)) {
                continue;
            }

            $items[] = [
                'label' => $field['label'],
                'value' => $value,
                'url' => $this->contactUrl($field['key'], $field['type'], $value),
                'note' => $field['note'] ?? null,
            ];
        }

        return $items;
    }

    private function contactUrl(string $key, string $type, string $value): ?string
    {
        if ($key === 'contact_whatsapp') {
            return 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $value));
        }

        return match ($type) {
            'email' => 'mailto:'.$value,
            'url' => $value,
            default => null,
        };
    }

    /**
     * Store a visitor message for the admin inbox.
     */
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = ContactMessage::query()->create($request->validated());

        $this->sendMessageEmails($contactMessage);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terima kasih! Pesan Anda sudah terkirim.',
        ]);

        return to_route('contact.index');
    }

    /**
     * Email the message to the configured recipient and a confirmation to the
     * sender. Mail failures are reported but never lose the stored message.
     */
    private function sendMessageEmails(ContactMessage $contactMessage): void
    {
        try {
            Mail::to($this->systemSettings->get(SystemSetting::CONTACT_RECIPIENT_EMAIL, config('mail.from.address')))->send(new ContactMessageReceived($contactMessage));
            Mail::to($contactMessage->email)->send(new ContactMessageConfirmation($contactMessage));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
