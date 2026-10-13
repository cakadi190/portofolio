<?php

namespace App\Http\Controllers;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\StoreGuestbookEntryRequest;
use App\Models\GuestbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GuestbookController extends Controller
{
    private const int MAX_ROOT_ENTRIES = 100;

    /**
     * Show the newest approved messages with their nested replies.
     */
    public function index(): Response
    {
        return Inertia::render('guestbook/index', [
            'entries' => $this->entryTree(),
        ]);
    }

    /**
     * Store a message or reply by the signed-in user. Visitor messages wait
     * for moderation; blogging staff are published immediately.
     */
    public function store(StoreGuestbookEntryRequest $request): RedirectResponse
    {
        $user = $request->user();

        $entry = new GuestbookEntry($request->safe()->only('body'));
        $entry->user_id = $user->id;
        $entry->parent_id = $request->validated('parent_id');
        $entry->status = $user->canManageBlog() ? BlogCommentStatus::Approved : BlogCommentStatus::Pending;
        $entry->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $entry->status === BlogCommentStatus::Approved
                ? 'Pesan Anda telah dikirim.'
                : 'Terima kasih! Pesan Anda akan tampil setelah ditinjau.',
        ]);

        return to_route('guestbook.index');
    }

    /**
     * Approved entries as a nested tree. A reply whose ancestor is not
     * approved is hidden together with that ancestor.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function entryTree(): Collection
    {
        $byParent = GuestbookEntry::query()
            ->approved()
            ->with('user:id,name')
            ->oldest()
            ->get()
            ->groupBy('parent_id');

        $build = function (?int $parentId) use (&$build, $byParent): Collection {
            return $byParent->get($parentId, collect())->map(fn (GuestbookEntry $entry): array => [
                'id' => $entry->id,
                'body' => $entry->body,
                'author' => $entry->user?->name ?? 'Pengguna terhapus',
                'createdAt' => $entry->created_at,
                'replies' => $build($entry->id)->values(),
            ])->values();
        };

        return $build(null)->reverse()->take(self::MAX_ROOT_ENTRIES)->values();
    }
}
