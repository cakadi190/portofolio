<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoteRequest;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/notes/index', [
            'notes' => $this->paginateTable(
                Note::query()->with('tags:id,name')->orderByDesc('created_at'),
                $request,
                ['title'],
                ['title', 'is_published', 'published_at'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'is_published', 'published_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/notes/form', [
            'note' => null,
            'tags' => $this->tags(),
            'authors' => $this->authors(),
        ]);
    }

    public function edit(Note $note): Response
    {
        return Inertia::render('admin/notes/form', [
            'note' => $note->load('tags:id'),
            'tags' => $this->tags(),
            'authors' => $this->authors(),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('tags');
        $data['user_id'] ??= $request->user()->id;

        $note = Note::query()->create($data);
        $note->tags()->sync($request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan berhasil ditambahkan.']);

        return to_route('admin.notes.edit', $note);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->safe()->except('tags'));
        $note->tags()->sync($request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan berhasil diperbarui.']);

        return to_route('admin.notes.edit', $note);
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan berhasil dihapus.']);

        return to_route('admin.notes.index');
    }

    /**
     * @return Collection<int, User>
     */
    private function authors(): Collection
    {
        return User::query()
            ->whereIn('account_type', [UserRole::Admin, UserRole::Redaktur])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Tag>
     */
    private function tags(): Collection
    {
        return Tag::query()->orderBy('name')->get(['id', 'name']);
    }
}
