<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Concerns\ResolvesEditorialAuthor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NoteRequest;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    use PaginatesTables, ResolvesEditorialAuthor;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/notes/index', [
            'notes' => $this->paginateTable(
                Note::query()->manageableBy($request->user())->with('tags:id,name')->orderByDesc('created_at'),
                $request,
                ['title'],
                ['title', 'is_published', 'published_at'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'is_published', 'published_at']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/notes/form', [
            'note' => null,
            'canAssignAuthor' => $request->user()->can('assignAuthor', Note::class),
            'tags' => $this->tags(),
            'authors' => $this->authors(),
        ]);
    }

    public function edit(Request $request, Note $note): Response
    {
        Gate::authorize('update', $note);

        return Inertia::render('admin/notes/form', [
            'canAssignAuthor' => $request->user()->can('assignAuthor', Note::class),
            'note' => [
                ...$note->load('tags:id')->toArray(),
                'translations' => $note->translationPayload(),
            ],
            'tags' => $this->tags(),
            'authors' => $this->authors(),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('tags');
        $data['user_id'] = $this->resolveAuthorId($request->user(), (bool) ($data['is_published'] ?? false), null, $data['user_id'] ?? null);

        $note = Note::query()->create($data);
        $note->tags()->sync($request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.catatan_berhasil_ditambahkan')]);

        return to_route('admin.notes.edit', $note);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $data = $request->safe()->except('tags');
        $data['user_id'] = $this->resolveAuthorId($request->user(), (bool) ($data['is_published'] ?? false), $note->user_id, $data['user_id'] ?? null);

        $note->update($data);
        $note->tags()->sync($request->validated('tags', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.catatan_berhasil_diperbarui')]);

        return to_route('admin.notes.edit', $note);
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $note->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.catatan_berhasil_dihapus')]);

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
