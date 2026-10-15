<?php

namespace App\Http\Controllers;

use App\Enums\ContactReason;
use App\Enums\SystemSettingGroup;
use App\Http\Requests\ContactMessageRequest;
use App\Jobs\SendContactAdminNotification;
use App\Jobs\SendContactReceipt;
use App\Models\ContactMessage;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
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
     * Store a visitor message for the admin inbox, then queue the receipt to the sender and the notification to the admin.
     */
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        $contactMessage = ContactMessage::query()->create($request->validated());

        SendContactReceipt::dispatchAfterResponse($contactMessage);
        SendContactAdminNotification::dispatchAfterResponse($contactMessage);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terima kasih! Pesan Anda sudah terkirim.',
        ]);

        return to_route('contact.index');
    }
}
