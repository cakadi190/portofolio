<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BlogCommentStatus;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogCommentRequest;
use App\Models\BlogComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlogCommentController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BlogComment::class);

        $status = BlogCommentStatus::tryFrom((string) $request->query('status', ''));

        return Inertia::render('admin/blog-comments/index', [
            'blogComments' => $this->paginateTable(
                BlogComment::query()
                    ->with(['post:id,title,slug', 'user:id,name,email', 'parent:id,body'])
                    ->withCount('replies')
                    ->when($status, fn ($query) => $query->where('status', $status))
                    ->latest(),
                $request,
                ['body', 'post.title', 'user.name'],
                ['status', 'created_at'],
            ),
            'filters' => [...$this->tableFilters($request, ['status', 'created_at']), 'status' => $status?->value],
            'statuses' => BlogCommentStatus::options(),
        ]);
    }

    /**
     * Inspect a comment together with its ancestors and every reply below it.
     */
    public function show(BlogComment $blogComment): Response
    {
        Gate::authorize('view', $blogComment);

        $blogComment->load(['post:id,title,slug', 'user:id,name,email']);

        return Inertia::render('admin/blog-comments/show', [
            'blogComment' => $this->present($blogComment),
            'ancestors' => $this->ancestors($blogComment)->map(fn (BlogComment $comment): array => $this->present($comment))->values(),
            'descendants' => $this->descendants($blogComment),
            'statuses' => BlogCommentStatus::options(),
        ]);
    }

    public function update(BlogCommentRequest $request, BlogComment $blogComment): RedirectResponse
    {
        Gate::authorize('update', $blogComment);

        $blogComment->body = $request->validated('body');
        $blogComment->status = $request->validated('status');
        $blogComment->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komentar berhasil diperbarui.']);

        return back();
    }

    /**
     * Replies are lifted to the deleted comment's parent, not deleted.
     */
    public function destroy(BlogComment $blogComment): RedirectResponse
    {
        Gate::authorize('delete', $blogComment);

        $blogComment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Komentar berhasil dihapus. Balasannya dipertahankan.']);

        return to_route('admin.blog-comments.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(BlogComment $comment, int $depth = 0): array
    {
        return [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'status' => $comment->status->value,
            'depth' => $depth,
            'created_at' => $comment->created_at,
            'user' => $comment->user ? $comment->user->only(['id', 'name', 'email']) : null,
            'post' => $comment->relationLoaded('post') ? $comment->post->only(['id', 'title', 'slug']) : null,
        ];
    }

    /**
     * Parents from the root down to the direct parent.
     *
     * @return Collection<int, BlogComment>
     */
    private function ancestors(BlogComment $comment): Collection
    {
        $chain = collect();
        $seen = [$comment->id];
        $current = $comment;

        while ($current->parent_id !== null && ! in_array($current->parent_id, $seen, true)) {
            $current = BlogComment::query()->with('user:id,name,email')->find($current->parent_id);

            if ($current === null) {
                break;
            }

            $seen[] = $current->id;
            $chain->prepend($current);
        }

        return $chain;
    }

    /**
     * Every reply below the comment, depth first, with its depth below the comment.
     *
     * @return list<array<string, mixed>>
     */
    private function descendants(BlogComment $root): array
    {
        $byParent = BlogComment::query()
            ->with('user:id,name,email')
            ->whereIn('id', $this->descendantIds($root))
            ->oldest()
            ->get()
            ->groupBy('parent_id');

        $flat = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$flat, $byParent): void {
            foreach ($byParent->get($parentId, []) as $reply) {
                $flat[] = $this->present($reply, $depth);
                $walk($reply->id, $depth + 1);
            }
        };
        $walk($root->id, 1);

        return $flat;
    }

    /**
     * @return list<int>
     */
    private function descendantIds(BlogComment $root): array
    {
        $ids = [];
        $frontier = [$root->id];

        while ($frontier !== []) {
            $frontier = BlogComment::query()->whereIn('parent_id', $frontier)->pluck('id')->diff($ids)->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }
}
