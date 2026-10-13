<?php

use App\Enums\BlogCommentStatus;
use App\Models\GuestbookEntry;
use App\Models\User;

test('guests cannot write in the guestbook', function () {
    $this->post(route('guestbook.store'), ['body' => 'Halo'])->assertRedirect(route('login'));

    expect(GuestbookEntry::count())->toBe(0);
});

test('a user message waits for moderation while staff messages are published', function () {
    $this->actingAs(User::factory()->create())->post(route('guestbook.store'), ['body' => '  Halo  '])
        ->assertRedirect(route('guestbook.index'));
    $this->actingAs(User::factory()->redaktur()->create())->post(route('guestbook.store'), ['body' => 'Selamat datang']);

    expect(GuestbookEntry::query()->oldest('id')->pluck('status', 'body')->all())
        ->toBe(['Halo' => BlogCommentStatus::Pending, 'Selamat datang' => BlogCommentStatus::Approved]);
});

test('replies nest and must target an approved entry', function () {
    $user = User::factory()->create();
    $approved = GuestbookEntry::factory()->create();
    $pending = GuestbookEntry::factory()->pending()->create();

    $this->actingAs($user)->post(route('guestbook.store'), ['body' => 'Balasan', 'parent_id' => $approved->id])->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('guestbook.store'), ['body' => 'Gagal', 'parent_id' => $pending->id])->assertSessionHasErrors('parent_id');

    expect($approved->replies()->count())->toBe(1);
});

test('the guestbook shows only approved entries as a nested tree', function () {
    $root = GuestbookEntry::factory()->create(['body' => 'Akar']);
    $reply = GuestbookEntry::factory()->replyTo($root)->create(['body' => 'Anak']);
    GuestbookEntry::factory()->replyTo($reply)->create(['body' => 'Cucu']);
    GuestbookEntry::factory()->pending()->create(['body' => 'Disembunyikan']);

    $this->get(route('guestbook.index'))->assertInertia(fn ($page) => $page
        ->component('guestbook/index')
        ->has('entries', 1)
        ->where('entries.0.body', 'Akar')
        ->where('entries.0.replies.0.body', 'Anak')
        ->where('entries.0.replies.0.replies.0.body', 'Cucu'));
});

test('deleting an entry lifts its replies one level', function () {
    $root = GuestbookEntry::factory()->create();
    $middle = GuestbookEntry::factory()->replyTo($root)->create();
    $leaf = GuestbookEntry::factory()->replyTo($middle)->create();

    $middle->delete();

    expect($leaf->refresh()->parent_id)->toBe($root->id);
});

test('only blogging staff moderate the guestbook', function () {
    $entry = GuestbookEntry::factory()->pending()->create();

    $this->actingAs(User::factory()->create())->get(route('admin.guestbook-entries.index'))->assertForbidden();

    $this->actingAs(User::factory()->redaktur()->create())
        ->put(route('admin.guestbook-entries.update', $entry), ['body' => 'Disunting', 'status' => 'approved'])
        ->assertRedirect();

    expect($entry->refresh())->body->toBe('Disunting')->status->toBe(BlogCommentStatus::Approved);

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.guestbook-entries.destroy', $entry))
        ->assertRedirect(route('admin.guestbook-entries.index'));

    expect(GuestbookEntry::count())->toBe(0);
});
