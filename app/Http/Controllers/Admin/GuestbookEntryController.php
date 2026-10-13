<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogCommentStatus;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuestbookEntryRequest;
use App\Models\GuestbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestbookEntryController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        $status = BlogCommentStatus::tryFrom((string) $request->query('status', ''));

        return Inertia::render('admin/guestbook-entries/index', [
            'guestbookEntries' => $this->paginateTable(
                GuestbookEntry::query()
                    ->with(['user:id,name,email', 'parent:id,body'])
                    ->withCount('replies')
                    ->when($status, fn ($query) => $query->where('status', $status))
                    ->latest(),
                $request,
                ['body', 'user.name'],
                ['status', 'created_at'],
            ),
            'filters' => [...$this->tableFilters($request, ['status', 'created_at']), 'status' => $status?->value],
            'statuses' => BlogCommentStatus::options(),
        ]);
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
}
