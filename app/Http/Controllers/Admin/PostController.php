<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAuthorRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/posts/index', [
            'posts' => $this->paginateTable(
                Post::query()
                    ->with(['tags:id,name', 'categories:id,name', 'author:id,name'])
                    ->withCount(['tags', 'categories'])
                    ->orderByDesc('created_at'),
                $request,
                ['title', 'author.name'],
                ['title', 'is_published', 'published_at'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'is_published', 'published_at']),
            ...$this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/posts/form', [
            'post' => null,
            ...$this->options(),
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('admin/posts/form', [
            'post' => $post->load(['tags:id', 'categories:id']),
            ...$this->options(),
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['tags', 'categories']);
        $data['user_id'] ??= $request->user()->id;

        $post = Post::query()->create($data);
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel berhasil ditambahkan.']);

        return to_route('admin.posts.edit', $post);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->safe()->except(['tags', 'categories']);

        $post->update($data);
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel berhasil diperbarui.']);

        return to_route('admin.posts.edit', $post);
    }

    public function updateAuthor(PostAuthorRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penulis artikel berhasil diganti.']);

        return back();
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel berhasil dihapus.']);

        return to_route('admin.posts.index');
    }

    private function syncRelations(Post $post, PostRequest $request): void
    {
        $post->tags()->sync($request->validated('tags', []));
        $post->categories()->sync($request->validated('categories', []));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'categories' => PostCategory::query()->orderBy('name')->get(['id', 'name']),
            'authors' => User::query()
                ->whereIn('account_type', [UserRole::Admin, UserRole::Redaktur])
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
