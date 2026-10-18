<?php

use App\Models\Note;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

describe('ownership policy', function () {
    test('admins manage any post or note', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->redaktur()->create();

        foreach ([Post::factory()->create(['user_id' => $owner->id]), Note::factory()->create(['user_id' => $owner->id])] as $content) {
            expect(Gate::forUser($admin)->allows('update', $content))->toBeTrue()
                ->and(Gate::forUser($admin)->allows('delete', $content))->toBeTrue();
        }
    });

    test('an editor manages only their own content', function () {
        $editor = User::factory()->redaktur()->create();
        $other = User::factory()->redaktur()->create();

        $own = Post::factory()->create(['user_id' => $editor->id]);
        $theirs = Post::factory()->create(['user_id' => $other->id]);
        $ownNote = Note::factory()->create(['user_id' => $editor->id]);
        $theirNote = Note::factory()->create(['user_id' => $other->id]);

        expect(Gate::forUser($editor)->allows('update', $own))->toBeTrue()
            ->and(Gate::forUser($editor)->allows('delete', $own))->toBeTrue()
            ->and(Gate::forUser($editor)->allows('update', $ownNote))->toBeTrue()
            ->and(Gate::forUser($editor)->allows('update', $theirs))->toBeFalse()
            ->and(Gate::forUser($editor)->allows('delete', $theirs))->toBeFalse()
            ->and(Gate::forUser($editor)->allows('update', $theirNote))->toBeFalse();
    });

    test('an editor may take over an unowned draft but not an unowned published post', function () {
        $editor = User::factory()->redaktur()->create();
        $draft = Post::factory()->create(['user_id' => null, 'is_published' => false]);
        $orphan = Post::factory()->create(['user_id' => null, 'is_published' => true]);

        expect(Gate::forUser($editor)->allows('update', $draft))->toBeTrue()
            ->and(Gate::forUser($editor)->allows('update', $orphan))->toBeFalse();
    });

    test('plain users manage nothing and only admins assign authors', function () {
        $user = User::factory()->create();
        $editor = User::factory()->redaktur()->create();
        $draft = Post::factory()->create(['user_id' => null, 'is_published' => false]);

        expect(Gate::forUser($user)->allows('update', $draft))->toBeFalse()
            ->and(Gate::forUser($editor)->allows('assignAuthor', Post::class))->toBeFalse()
            ->and(Gate::forUser(User::factory()->admin()->create())->allows('assignAuthor', Post::class))->toBeTrue();
    });
});

describe('endpoints', function () {
    test('an editor gets 403 editing, updating and deleting a colleague\'s post', function () {
        $editor = User::factory()->redaktur()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->redaktur()->create()->id]);

        $this->actingAs($editor)->get(route('admin.posts.edit', $post))->assertForbidden();
        $this->actingAs($editor)->put(route('admin.posts.update', $post), ['title' => 'Dibajak', 'content' => '<p>x</p>'])->assertForbidden();
        $this->actingAs($editor)->delete(route('admin.posts.destroy', $post))->assertForbidden();

        expect($post->fresh())->not->toBeNull()->title->not->toBe('Dibajak');
    });

    test('an editor gets 403 on a colleague\'s note and cannot restore their post', function () {
        $editor = User::factory()->redaktur()->create();
        $other = User::factory()->redaktur()->create();
        $note = Note::factory()->create(['user_id' => $other->id]);
        $trashed = Post::factory()->create(['user_id' => $other->id]);
        $trashed->delete();

        $this->actingAs($editor)->put(route('admin.notes.update', $note), ['title' => 'Dibajak', 'content' => '<p>x</p>'])->assertForbidden();
        $this->actingAs($editor)->delete(route('admin.notes.destroy', $note))->assertForbidden();
        $this->actingAs($editor)->patch(route('admin.posts.restore', $trashed))->assertForbidden();

        expect(Note::find($note->id))->not->toBeNull()
            ->and($trashed->fresh()->trashed())->toBeTrue();
    });

    test('the post list only shows an editor their own posts and unowned drafts', function () {
        $editor = User::factory()->redaktur()->create();
        Post::factory()->create(['title' => 'Milik saya', 'user_id' => $editor->id]);
        Post::factory()->create(['title' => 'Draf bebas', 'user_id' => null, 'is_published' => false]);
        Post::factory()->create(['title' => 'Milik orang', 'user_id' => User::factory()->redaktur()->create()->id]);

        $this->actingAs($editor)->get(route('admin.posts.index'))
            ->assertInertia(fn ($page) => $page->has('posts.data', 2));
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.posts.index'))
            ->assertInertia(fn ($page) => $page->has('posts.data', 3));
    });

    test('only an admin can reassign the author of a post', function () {
        $post = Post::factory()->create(['user_id' => null, 'is_published' => false]);
        $author = User::factory()->redaktur()->create();

        $this->actingAs(User::factory()->redaktur()->create())
            ->patch(route('admin.posts.author.update', $post), ['user_id' => $author->id])->assertForbidden();

        expect($post->fresh()->user_id)->toBeNull();
    });
});

describe('author assignment', function () {
    test('a draft has no author until it is published', function () {
        $editor = User::factory()->redaktur()->create();

        $this->actingAs($editor)->post(route('admin.posts.store'), ['title' => 'Draf', 'content' => '<p>x</p>', 'is_published' => false]);
        $this->actingAs($editor)->post(route('admin.notes.store'), ['title' => 'Catatan draf', 'content' => '<p>x</p>', 'is_published' => false]);

        expect(Post::where('title', 'Draf')->value('user_id'))->toBeNull()
            ->and(Note::where('title', 'Catatan draf')->value('user_id'))->toBeNull();
    });

    test('the first editor to publish a draft becomes its author and keeps it', function () {
        $first = User::factory()->redaktur()->create();
        $second = User::factory()->redaktur()->create();
        $post = Post::factory()->create(['user_id' => null, 'is_published' => false]);
        $payload = fn (bool $published) => ['title' => $post->title, 'content' => '<p>x</p>', 'is_published' => $published];

        $this->actingAs($first)->put(route('admin.posts.update', $post), $payload(true));
        expect($post->fresh()->user_id)->toBe($first->id);

        $this->actingAs($first)->put(route('admin.posts.update', $post), $payload(false));
        expect($post->fresh()->user_id)->toBe($first->id);

        $this->actingAs($second)->put(route('admin.posts.update', $post), $payload(true))->assertForbidden();
        expect($post->fresh()->user_id)->toBe($first->id);
    });

    test('an editor cannot name another author when publishing', function () {
        $editor = User::factory()->redaktur()->create();
        $other = User::factory()->redaktur()->create();

        $this->actingAs($editor)->post(route('admin.posts.store'), [
            'title' => 'Klaim palsu', 'content' => '<p>x</p>', 'is_published' => true, 'user_id' => $other->id,
        ]);

        expect(Post::where('title', 'Klaim palsu')->value('user_id'))->toBe($editor->id);
    });

    test('an admin can name the author of a draft explicitly', function () {
        $author = User::factory()->redaktur()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
            'title' => 'Ditugaskan', 'content' => '<p>x</p>', 'is_published' => false, 'user_id' => $author->id,
        ]);

        expect(Post::where('title', 'Ditugaskan')->value('user_id'))->toBe($author->id);
    });
});

describe('editorial overviews', function () {
    test('the dashboard counts only what an editor may manage', function () {
        $editor = User::factory()->redaktur()->create();
        Post::factory()->create(['title' => 'Milik saya', 'user_id' => $editor->id]);
        Post::factory()->create(['title' => 'Draf bebas', 'user_id' => null, 'is_published' => false]);
        Post::factory()->create(['title' => 'Rahasia orang', 'user_id' => User::factory()->redaktur()->create()->id, 'is_published' => false]);

        $this->actingAs($editor)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('summary.blog.posts_total', 2)
            ->where('summary.blog.posts_draft', 1)
            ->has('summary.blog.recent_posts', 2));
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('summary.blog.posts_total', 3));
    });

    test('the calendar hides a colleague\'s scheduled post from an editor', function () {
        $editor = User::factory()->redaktur()->create();
        $scheduled = fn (string $title, ?int $owner) => Post::factory()->create([
            'title' => $title, 'user_id' => $owner, 'published_at' => now()->addDays(3),
        ]);
        $scheduled('Jadwal saya', $editor->id);
        $scheduled('Jadwal orang', User::factory()->redaktur()->create()->id);

        $response = $this->actingAs($editor)->getJson(route('admin.calendar-events', ['month' => now()->addDays(3)->format('Y-m')]))->assertOk();

        expect(collect($response->json('upcoming'))->pluck('title')->all())->toBe(['Jadwal saya'])
            ->and(collect($response->json('events'))->pluck('title')->all())->toBe(['Jadwal saya']);
    });
});
