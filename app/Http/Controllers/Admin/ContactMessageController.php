<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactReplyStatus;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactMessageReplyRequest;
use App\Jobs\SendContactReply;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\Media;
use App\Support\AppLocale;
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
            ContactMessage::query()->with('replies.author:id,name')->latest(),
            $request,
            ['name', 'email'],
            ['name', 'email', 'created_at'],
        );

        return Inertia::render('admin/contact-messages/index', [
            'messages' => $messages->through(fn (ContactMessage $message): array => [
                ...$message->only(['id', 'name', 'email', 'message', 'read_at', 'created_at']),
                'reason' => $message->reason->value,
                'reason_label' => $message->reason->label(),
                'locale' => $this->messageLocale($message),
                'locale_label' => __('locales.'.$this->messageLocale($message).'.label'),
                'default_subject' => $this->defaultSubject($message),
                'replies' => $message->replies->map(fn (ContactMessageReply $reply): array => [
                    'id' => $reply->id,
                    'subject' => $reply->subject,
                    'status' => $reply->status->value,
                    'status_label' => $reply->status->label(),
                    'error' => $reply->error,
                    'author' => $reply->author?->name,
                    'attachments' => Media::query()->whereIn('path', $reply->attachments ?? [])->pluck('name')->all(),
                    'created_at' => $reply->created_at,
                ])->all(),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => $contactMessage->read_at ? __('flash.pesan_ditandai_sudah_dibaca') : __('flash.pesan_ditandai_belum_dibaca')]);

        return to_route('admin.contact-messages.index');
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.pesan_berhasil_dihapus')]);

        return to_route('admin.contact-messages.index');
    }

    /**
     * Save an emailed reply and queue its delivery to the sender.
     */
    public function reply(ContactMessageReplyRequest $request, ContactMessage $contactMessage): RedirectResponse
    {
        $reply = $contactMessage->replies()->create([
            'user_id' => $request->user()->id,
            'subject' => $request->validated('subject') ?: $this->defaultSubject($contactMessage),
            'body' => $request->validated('body'),
            'attachments' => $request->validated('attachments') ?: null,
            'status' => ContactReplyStatus::Queued,
        ]);

        SendContactReply::dispatchAfterResponse($reply);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.balasan_masuk_antrean_dan_sedang_dikirim')]);

        return to_route('admin.contact-messages.index');
    }

    private function defaultSubject(ContactMessage $contactMessage): string
    {
        $adminLocale = app()->getLocale();
        app()->setLocale($this->messageLocale($contactMessage));

        try {
            return __('mail.reply.subject', ['reason' => $contactMessage->reason->label()]);
        } finally {
            app()->setLocale($adminLocale);
        }
    }

    /**
     * The language the sender used on the contact form, which also picks the email template.
     */
    private function messageLocale(ContactMessage $contactMessage): string
    {
        return in_array($contactMessage->locale, AppLocale::available(), true) ? $contactMessage->locale : AppLocale::DEFAULT;
    }
}
