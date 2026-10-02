<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        $messages = $this->paginateTable(
            ContactMessage::query()->latest(),
            $request,
            ['name', 'email'],
            ['name', 'email', 'created_at'],
        );

        return Inertia::render('admin/contact-messages/index', [
            'messages' => $messages->through(fn (ContactMessage $message): array => [
                ...$message->toArray(),
                'reason' => $message->reason->value,
                'reason_label' => $message->reason->label(),
            ]),
            'filters' => $this->tableFilters($request, ['name', 'email', 'created_at']),
        ]);
    }

    /**
     * Toggle the read state of a message.
     */
    public function update(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update(['read_at' => $contactMessage->read_at ? null : now()]);

        return to_route('admin.contact-messages.index');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan berhasil dihapus.']);

        return to_route('admin.contact-messages.index');
    }
}
