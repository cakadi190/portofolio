<?php

use App\Enums\BlogCommentStatus;
use App\Models\GuestbookEntry;
use App\Models\User;

test('a signed-out visitor needs a name and email and waits for moderation', function () {
    $this->post(route('guestbook.store'), ['body' => '<p>Halo</p>'])->assertSessionHasErrors(['guest_name', 'guest_email']);

    $this->post(route('guestbook.store'), ['guest_name' => 'Tamu', 'guest_email' => 'tamu@example.com', 'body' => '<p>Halo</p>'])
        ->assertRedirect(route('guestbook.index'));

    $entry = GuestbookEntry::sole();

    expect($entry->user_id)->toBeNull()
        ->and($entry->guest_name)->toBe('Tamu')
        ->and($entry->guest_email)->toBe('tamu@example.com')
        ->and($entry->status)->toBe(BlogCommentStatus::Pending);
});

test('a signed-in user is published immediately without leaving guest details', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('guestbook.store'), ['body' => '<p>Halo</p>', 'guest_name' => 'Palsu'])
        ->assertRedirect(route('guestbook.index'));

    $entry = GuestbookEntry::sole();

    expect($entry->status)->toBe(BlogCommentStatus::Approved)
        ->and($entry->user_id)->toBe($user->id)
        ->and($entry->guest_name)->toBeNull();
});

test('submitted markup is reduced to a safe whitelist and legacy plain text is escaped on output', function () {
    $this->actingAs(User::factory()->create())->post(route('guestbook.store'), [
        'body' => '<p onclick="x()">Halo <strong>dunia</strong></p><script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">klik</a>',
    ]);

    expect(GuestbookEntry::sole()->body)
        ->toContain('<strong>dunia</strong>')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:');

    GuestbookEntry::factory()->create(['body' => "Satu <i>dua</i>\nTiga"]);
    GuestbookEntry::factory()->create(['body' => 'a < b & <script>alert(1)</script>']);

    $this->get(route('guestbook.index'))->assertInertia(fn ($page) => $page
        ->where('entries', fn ($entries) => collect($entries)->every(fn ($entry) => ! str_contains($entry['body'], '<script'))));
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

test('staff see guestbook threads nested and can reply from the admin', function () {
    $admin = User::factory()->admin()->create();
    $root = GuestbookEntry::factory()->create();
    $child = GuestbookEntry::factory()->create(['parent_id' => $root->id]);
    GuestbookEntry::factory()->create(['parent_id' => $child->id]);

    $this->actingAs($admin)->get(route('admin.guestbook-entries.index'))->assertInertia(fn ($page) => $page
        ->has('guestbookEntries.data', 1)
        ->where('guestbookEntries.data.0.replies.0.id', $child->id)
        ->has('guestbookEntries.data.0.replies.0.replies', 1));

    $this->actingAs($admin)->post(route('admin.guestbook-entries.reply', $root), ['body' => '<p onclick="x()">Terima kasih</p>'])
        ->assertRedirect();

    $reply = GuestbookEntry::query()->where('user_id', $admin->id)->sole();

    expect($reply->parent_id)->toBe($root->id)
        ->and($reply->status)->toBe(BlogCommentStatus::Approved)
        ->and($reply->body)->not->toContain('onclick');

    $this->actingAs($admin)->post(route('admin.guestbook-entries.reply', $root), ['body' => ''])->assertSessionHasErrors('body');
    $this->actingAs(User::factory()->create())->post(route('admin.guestbook-entries.reply', $root), ['body' => 'x'])->assertForbidden();
});
