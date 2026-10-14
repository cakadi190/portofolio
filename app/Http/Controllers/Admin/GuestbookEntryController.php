<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogCommentStatus;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuestbookEntryRequest;
use App\Http\Requests\Admin\GuestbookReplyRequest;
use App\Models\GuestbookEntry;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GuestbookEntryController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        $status = BlogCommentStatus::tryFrom((string) $request->query('status', ''));

        $threads = $this->paginateTable(
            GuestbookEntry::query()
                ->with('user:id,name,email,avatar')
                ->whereNull('parent_id')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest(),
            $request,
            ['body', 'user.name', 'guest_name'],
        );

        $threads->setCollection($this->withReplyTrees($threads->getCollection()));

        return Inertia::render('admin/guestbook-entries/index', [
            'guestbookEntries' => Inertia::scroll($threads),
            'filters' => [...$this->tableFilters($request), 'status' => $status?->value],
            'statuses' => BlogCommentStatus::options(),
        ]);
    }

    /**
     * Staff replies are published immediately under the signed-in account.
     */
    public function reply(GuestbookReplyRequest $request, GuestbookEntry $guestbookEntry): RedirectResponse
    {
        $reply = new GuestbookEntry($request->safe()->only('body'));
        $reply->user_id = $request->user()->id;
        $reply->parent_id = $guestbookEntry->id;
        $reply->status = BlogCommentStatus::Approved;
        $reply->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Balasan berhasil dikirim.']);

        return back();
    }

    public function update(GuestbookEntryRequest $request, GuestbookEntry $guestbookEntry): RedirectResponse
    {
        $guestbookEntry->body = $request->validated('body');
        $guestbookEntry->status = $request->validated('status');
        $guestbookEntry->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan buku tamu berhasil diperbarui.']);

        return back();
    }

    /**
     * Replies are lifted to the deleted entry's parent, not deleted.
     */
    public function destroy(GuestbookEntry $guestbookEntry): RedirectResponse
    {
        $guestbookEntry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan dihapus. Balasannya dipertahankan.']);

        return to_route('admin.guestbook-entries.index');
    }

    /**
     * Turn the given root entries into nested arrays, loading each level of replies in one query.
     *
     * @param  Collection<int, GuestbookEntry>  $roots
     * @return Collection<int, array<string, mixed>>
     */
    private function withReplyTrees(Collection $roots): Collection
    {
        $byParent = collect();
        $level = $roots->pluck('id');

        while ($level->isNotEmpty()) {
            $children = GuestbookEntry::query()
                ->with('user:id,name,email,avatar')
                ->whereIn('parent_id', $level)
                ->oldest()
                ->get();

            $byParent = $byParent->union($children->groupBy('parent_id'));
            $level = $children->pluck('id');
        }

        $build = function (GuestbookEntry $entry) use (&$build, $byParent): array {
            return [
                'id' => $entry->id,
                'parent_id' => $entry->parent_id,
                'body' => HtmlSanitizer::richText($entry->body),
                'raw_body' => $entry->body,
                'status' => $entry->status->value,
                'created_at' => $entry->created_at,
                'user' => $entry->user,
                'guest_name' => $entry->guest_name,
                'guest_email' => $entry->guest_email,
                'replies' => ($byParent->get($entry->id) ?? collect())->map(fn ($reply) => $build($reply))->values(),
            ];
        };

        return $roots->map(fn (GuestbookEntry $root) => $build($root))->values();
    }
}
