<?php

namespace App\Http\Controllers;

use App\Enums\BlogCommentStatus;
use App\Http\Requests\StoreBlogCommentRequest;
use App\Models\BlogComment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BlogCommentController extends Controller
{
    /**
     * Store a comment or reply by the signed-in user. Visitor comments wait
     * for moderation; blogging staff are published immediately.
     */
    public function store(StoreBlogCommentRequest $request, Post $post): RedirectResponse
    {
        abort_unless($post->is_published, 404);

        $user = $request->user();

        $comment = new BlogComment($request->safe()->only('body'));
        $comment->post_id = $post->id;
        $comment->user_id = $user->id;
        $comment->parent_id = $request->validated('parent_id');
        $comment->status = $user->canManageBlog() ? BlogCommentStatus::Approved : BlogCommentStatus::Pending;
        $comment->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $comment->status === BlogCommentStatus::Approved
                ? 'Komentar Anda telah dikirim.'
                : 'Terima kasih! Komentar Anda akan tampil setelah ditinjau.',
        ]);

        return to_route('blog.show', $post);
    }
}
