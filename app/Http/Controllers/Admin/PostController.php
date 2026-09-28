<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/posts/index', [
            'posts' => Post::query()
                ->with(['tags:id', 'categories:id'])
                ->withCount(['tags', 'categories'])
                ->orderByDesc('created_at')
                ->paginate(20),
            ...$this->options(),
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'tags', 'categories']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeUpload($request->file('cover_image'), 'posts');
        }

        $post = Post::query()->create($data);
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel berhasil ditambahkan.']);

        return to_route('admin.posts.index');
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image', 'tags', 'categories']);

        if ($request->hasFile('cover_image')) {
            $this->deleteUpload($post->cover_image);
            $data['cover_image'] = $this->storeUpload($request->file('cover_image'), 'posts');
        }

        $post->update($data);
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Artikel berhasil diperbarui.']);

        return to_route('admin.posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->deleteUpload($post->cover_image);
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
        ];
    }
}
