<?php

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use App\Models\Post;
use App\Models\User;

test('guests cannot post comments', function () {
    $post = Post::factory()->create();

    $this->post(route('blog.comments.store', $post), ['body' => 'Halo'])->assertRedirect(route('login'));

    expect(BlogComment::count())->toBe(0);
});

test('a user creates a root comment that waits for moderation', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => '  Halo  '])
        ->assertRedirect(route('blog.show', $post));

    $comment = BlogComment::firstOrFail();

    expect($comment)
        ->body->toBe('Halo')
        ->status->toBe(BlogCommentStatus::Pending)
        ->post_id->toBe($post->id)
        ->user_id->toBe($user->id)
        ->parent_id->toBeNull();
});

test('blogging staff comments are published immediately', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->redaktur()->create())
        ->post(route('blog.comments.store', $post), ['body' => 'Halo']);

    expect(BlogComment::firstOrFail()->status)->toBe(BlogCommentStatus::Approved);
});

test('replies nest to any depth', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();
    $parent = BlogComment::factory()->for($post)->create();

    foreach (range(1, 6) as $_) {
        $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => 'Balas', 'parent_id' => $parent->id])
            ->assertSessionDoesntHaveErrors();

        $parent = BlogComment::latest('id')->firstOrFail();
        $parent->update(['body' => 'Balas']);
        $parent->forceFill(['status' => BlogCommentStatus::Approved])->save();
    }

    expect(BlogComment::count())->toBe(7)
        ->and($parent->parent->parent->parent->parent->parent->parent->parent_id)->toBeNull();
});

test('client supplied ownership and status fields are ignored', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->post(route('blog.comments.store', $post), [
        'body' => 'Halo',
        'user_id' => $other->id,
        'status' => 'approved',
        'post_id' => Post::factory()->create()->id,
    ]);

    expect(BlogComment::firstOrFail())
        ->user_id->toBe($user->id)
        ->status->toBe(BlogCommentStatus::Pending)
        ->post_id->toBe($post->id);
});

test('invalid parents are rejected', function (string $case) {
    $post = Post::factory()->create();
    $parent = match ($case) {
        'other post' => BlogComment::factory()->create(),
        'pending' => BlogComment::factory()->for($post)->pending()->create(),
        'rejected' => BlogComment::factory()->for($post)->rejected()->create(),
        'missing' => null,
    };

    $this->actingAs(User::factory()->create())
        ->post(route('blog.comments.store', $post), ['body' => 'Halo', 'parent_id' => $parent?->id ?? 9999])
        ->assertSessionHasErrors('parent_id');

    expect(BlogComment::where('body', 'Halo')->count())->toBe(0);
})->with(['other post', 'pending', 'rejected', 'missing']);

test('the body is required and bounded', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => ''])->assertSessionHasErrors('body');
    $this->actingAs($user)->post(route('blog.comments.store', $post), ['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
});

test('comments cannot be posted on unpublished posts', function () {
    $post = Post::factory()->create(['is_published' => false]);

    $this->actingAs(User::factory()->create())
        ->post(route('blog.comments.store', $post), ['body' => 'Halo'])
        ->assertNotFound();
});

test('the blog page lists approved comments as a tree', function () {
    $post = Post::factory()->create();
    $root = BlogComment::factory()->for($post)->create(['body' => 'Akar']);
    $reply = BlogComment::factory()->replyTo($root)->create(['body' => 'Balasan']);
    BlogComment::factory()->replyTo($reply)->create(['body' => 'Cucu']);
    BlogComment::factory()->for($post)->pending()->create(['body' => 'Tertunda']);
    BlogComment::factory()->replyTo($root)->rejected()->create(['body' => 'Ditolak']);

    $this->get(route('blog.show', $post))->assertInertia(fn ($page) => $page
        ->has('comments', 1)
        ->where('comments.0.body', 'Akar')
        ->has('comments.0.replies', 1)
        ->where('comments.0.replies.0.replies.0.body', 'Cucu'));
});

test('deleting a comment keeps its replies and lifts them one level', function () {
    $root = BlogComment::factory()->create();
    $middle = BlogComment::factory()->replyTo($root)->create();
    $leaf = BlogComment::factory()->replyTo($middle)->create();

    $middle->delete();
    expect($leaf->refresh()->parent_id)->toBe($root->id);

    $root->delete();
    expect($leaf->refresh()->parent_id)->toBeNull();
});

test('deleting a post or a user preserves what should remain', function () {
    $comment = BlogComment::factory()->create();
    $comment->user->delete();
    expect($comment->refresh()->user_id)->toBeNull();

    $comment->post->delete();
    expect(BlogComment::count())->toBe(0);
});

describe('moderation', function () {
    test('staff list, inspect, update and delete comments', function (string $role) {
        $staff = User::factory()->{$role}()->create();
        $root = BlogComment::factory()->pending()->create();
        $reply = BlogComment::factory()->replyTo($root)->create();
        $deep = BlogComment::factory()->replyTo($reply)->create();

        $this->actingAs($staff)->get(route('admin.blog-comments.index', ['status' => 'pending']))
            ->assertInertia(fn ($page) => $page->has('blogComments.data', 1));

        $this->actingAs($staff)->get(route('admin.blog-comments.show', $reply))
            ->assertInertia(fn ($page) => $page
                ->has('ancestors', 1)
                ->where('ancestors.0.id', $root->id)
                ->has('descendants', 1)
                ->where('descendants.0.id', $deep->id)
                ->where('descendants.0.depth', 1));

        $this->actingAs($staff)->put(route('admin.blog-comments.update', $root), [
            'body' => 'Disunting', 'status' => 'approved', 'user_id' => 99, 'parent_id' => $deep->id,
        ])->assertRedirect();

        expect($root->refresh())->body->toBe('Disunting')->status->toBe(BlogCommentStatus::Approved)
            ->parent_id->toBeNull()->user_id->not->toBe(99);

        $this->actingAs($staff)->delete(route('admin.blog-comments.destroy', $reply))
            ->assertRedirect(route('admin.blog-comments.index'));

        expect(BlogComment::find($reply->id))->toBeNull()
            ->and($deep->refresh()->parent_id)->toBe($root->id);
    })->with(['admin', 'redaktur']);

    test('updates validate the status', function () {
        $comment = BlogComment::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.blog-comments.update', $comment), ['body' => 'x', 'status' => 'bogus'])
            ->assertSessionHasErrors('status');
    });

    test('there is no admin create route', function () {
        expect(Route::has('admin.blog-comments.store'))->toBeFalse();
    });

    test('commenters and guests cannot moderate, even their own comment', function () {
        $user = User::factory()->create();
        $own = BlogComment::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('admin.blog-comments.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.blog-comments.update', $own), ['body' => 'x', 'status' => 'approved'])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.blog-comments.destroy', $own))->assertForbidden();

        expect($own->refresh()->body)->not->toBe('x');
    });

    test('guests are sent to login', function () {
        $this->delete(route('admin.blog-comments.destroy', BlogComment::factory()->create()))->assertRedirect(route('login'));
    });
});
