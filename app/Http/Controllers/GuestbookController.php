<?php

namespace App\Http\Controllers;

use App\Enums\BlogCommentStatus;
use App\Http\Controllers\Concerns\NestsReplies;
use App\Http\Requests\StoreGuestbookEntryRequest;
use App\Models\GuestbookEntry;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GuestbookController extends Controller
{
    use NestsReplies;

    private const int ROOTS_PER_PAGE = 10;

    /**
     * Show the newest approved messages with their nested replies, loaded page by page as the visitor scrolls.
     */
    public function index(): Response
    {
        return Inertia::render('guestbook/index', [
            'entries' => Inertia::scroll(fn () => $this->entryPage()),
            'totalEntries' => GuestbookEntry::query()->approved()->count(),
        ]);
    }

    /**
     * Store a message or reply. Signed-in users are published immediately; a signed-out visitor leaves a name and
     * email (kept private) and waits for moderation.
     */
    public function store(StoreGuestbookEntryRequest $request): RedirectResponse
    {
        $user = $request->user();

        $entry = new GuestbookEntry($request->safe()->only('body'));
        $entry->user_id = $user?->id;
        $entry->guest_name = $user ? null : $request->validated('guest_name');
        $entry->guest_email = $user ? null : $request->validated('guest_email');
        $entry->parent_id = $request->validated('parent_id');
        $entry->status = $user ? BlogCommentStatus::Approved : BlogCommentStatus::Pending;
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
     * One page of approved root messages, each with its approved reply tree.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function entryPage(): LengthAwarePaginator
    {
        $roots = GuestbookEntry::query()
            ->approved()
            ->whereNull('parent_id')
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(self::ROOTS_PER_PAGE);

        $roots->setCollection($this->nestReplies(
            $roots->getCollection(),
            fn (Collection $parentIds): Collection => GuestbookEntry::query()
                ->approved()
                ->with('user:id,name,avatar')
                ->whereIn('parent_id', $parentIds)
                ->oldest()
                ->get(),
            fn (GuestbookEntry $entry, Collection $replies): array => [
                'id' => $entry->id,
                'body' => HtmlSanitizer::richText($entry->body),
                'author' => $entry->user?->name ?? $entry->guest_name ?? 'Pengguna terhapus',
                'authorAvatar' => $entry->user?->avatar,
                'createdAt' => $entry->created_at,
                'replies' => $replies,
            ],
        ));

        return $roots;
    }
}
