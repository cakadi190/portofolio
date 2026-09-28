<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/tags/index', [
            'tags' => Tag::query()->orderBy('name')->paginate(20),
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
