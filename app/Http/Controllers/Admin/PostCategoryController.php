<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostCategoryRequest;
use App\Models\PostCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/post-categories/index', [
            'postCategories' => PostCategory::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/post-categories/create');
    }

    public function store(PostCategoryRequest $request): RedirectResponse
    {
        PostCategory::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori artikel berhasil ditambahkan.']);

        return to_route('admin.post-categories.index');
    }

    public function edit(PostCategory $postCategory): Response
    {
        return Inertia::render('admin/post-categories/edit', [
            'postCategory' => $postCategory,
        ]);
    }

    public function update(PostCategoryRequest $request, PostCategory $postCategory): RedirectResponse
    {
        $postCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori artikel berhasil diperbarui.']);

        return to_route('admin.post-categories.index');
    }

    public function destroy(PostCategory $postCategory): RedirectResponse
    {
        $postCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori artikel berhasil dihapus.']);

        return to_route('admin.post-categories.index');
    }
}
