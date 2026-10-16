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
     * Store a comment or reply. Only blogging staff are published immediately; everyone else, signed in or
     * not, waits for moderation. A signed-out visitor leaves a name and email (kept private).
     */
    public function store(StoreBlogCommentRequest $request, Post $post): RedirectResponse
    {
        abort_unless($post->isLive(), 404);

        $user = $request->user();

        $comment = new BlogComment($request->safe()->only('body'));
        $comment->post_id = $post->id;
        $comment->user_id = $user?->id;
        $comment->guest_name = $user ? null : $request->validated('guest_name');
        $comment->guest_email = $user ? null : $request->validated('guest_email');
        $comment->parent_id = $request->validated('parent_id');
        $comment->status = $user?->canManageBlog() ? BlogCommentStatus::Approved : BlogCommentStatus::Pending;
        $comment->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $comment->status === BlogCommentStatus::Approved
                ? __('flash.comment.sent')
                : __('flash.comment.pending'),
        ]);

        return to_route('blog.show', $post);
    }
}
