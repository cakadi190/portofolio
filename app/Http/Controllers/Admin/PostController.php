<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Concerns\ResolvesEditorialAuthor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAuthorRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use PaginatesTables, ResolvesEditorialAuthor;

    public function index(Request $request): Response
    {
        $trashed = $request->boolean('trashed');
        $user = $request->user();

        return Inertia::render('admin/posts/index', [
            'trashed' => $trashed,
            'trashedCount' => Post::onlyTrashed()->manageableBy($user)->count(),
            'posts' => $this->paginateTable(
                Post::query()
                    ->manageableBy($user)
                    ->when($trashed, fn ($query) => $query->onlyTrashed())
                    ->orderByDesc('created_at'),
                $request,
                ['title'],
                ['title', 'is_published', 'published_at'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'is_published', 'published_at']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/posts/form', [
            'post' => null,
            'canAssignAuthor' => $request->user()->can('assignAuthor', Post::class),
            ...$this->options(),
        ]);
    }

    public function edit(Request $request, Post $post): Response
    {
        Gate::authorize('update', $post);

        return Inertia::render('admin/posts/form', [
            'canAssignAuthor' => $request->user()->can('assignAuthor', Post::class),
            'post' => [
                ...$post->load(['tags:id', 'categories:id'])->toArray(),
                'translations' => $post->translationPayload(),
            ],
            'previewUrl' => $post->isLive() ? null : $post->previewUrl(),
            ...$this->options(),
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['tags', 'categories']);
        $data['user_id'] = $this->resolveAuthorId($request->user(), (bool) ($data['is_published'] ?? false), null, $data['user_id'] ?? null);

        $post = Post::query()->create($this->withPublicationDate($data));
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.artikel_berhasil_ditambahkan')]);

        return to_route('admin.posts.edit', $post);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $data = $request->safe()->except(['tags', 'categories']);
        $data['user_id'] = $this->resolveAuthorId($request->user(), (bool) ($data['is_published'] ?? false), $post->user_id, $data['user_id'] ?? null);

        $post->update($this->withPublicationDate($data, $post));
        $this->syncRelations($post, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.artikel_berhasil_diperbarui')]);

        return to_route('admin.posts.edit', $post);
    }

    public function updateAuthor(PostAuthorRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('assignAuthor', Post::class);
        $post->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.penulis_artikel_berhasil_diganti')]);

        return back();
    }

    /**
     * Move a post to the trash; a post already in the trash is deleted for good.
     */
    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $permanent = $post->trashed();

        $permanent ? $post->forceDelete() : $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $permanent ? __('flash.artikel_dihapus_permanen') : __('flash.artikel_dipindahkan_ke_sampah')]);

        return to_route('admin.posts.index', $permanent ? ['trashed' => 1] : []);
    }

    public function restore(Post $post): RedirectResponse
    {
        Gate::authorize('restore', $post);

        $post->restore();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.artikel_berhasil_dipulihkan')]);

        return to_route('admin.posts.index', ['trashed' => 1]);
    }

    /**
     * A post published without a date goes live now; a future date schedules it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withPublicationDate(array $data, ?Post $post = null): array
    {
        if (($data['is_published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = $post?->published_at ?? now();
        }

        return $data;
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
