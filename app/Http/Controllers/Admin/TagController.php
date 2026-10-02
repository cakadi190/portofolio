<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/tags/index', [
            'tags' => $this->paginateTable(
                Tag::query()->orderBy('name'),
                $request,
                ['name'],
                ['name'],
            ),
            'filters' => $this->tableFilters($request, ['name']),
        ]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        Tag::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil ditambahkan.']);

        return to_route('admin.tags.index');
    }

    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil diperbarui.']);

        return to_route('admin.tags.index');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil dihapus.']);

        return to_route('admin.tags.index');
    }
}
