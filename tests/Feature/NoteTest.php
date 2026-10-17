<?php

use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the notes list shows only published notes and can filter by tag', function () {
    $react = Tag::factory()->create(['name' => 'react']);
    Note::factory()->create(['title' => 'Auth Context'])->tags()->attach($react);
    Note::factory()->create(['title' => 'Catatan Git']);
    Note::factory()->draft()->create(['title' => 'Draf Rahasia']);

    $this->get(route('notes.index'))->assertInertia(fn ($page) => $page
        ->component('notes/index')
        ->has('notes.data', 2)
        ->where('tags', ['react']));

    $this->get(route('notes.index', ['tag' => 'react']))->assertInertia(fn ($page) => $page
        ->has('notes.data', 1)
        ->where('notes.data.0.title', 'Auth Context')
        ->where('activeTag', 'react'));
});

test('a published note renders sanitized content, reading time and views', function () {
    $note = Note::factory()->create(['content' => '<p>Halo</p><script>alert(1)</script>']);
    DB::table('visitor_daily_stats')->insert([
        ['day' => today(), 'path' => "/notes/{$note->slug}", 'is_bot' => false, 'page_views' => 7],
        ['day' => today(), 'path' => "/notes/{$note->slug}", 'is_bot' => true, 'page_views' => 100],
    ]);

    $this->get(route('notes.show', $note))->assertInertia(fn ($page) => $page
        ->component('notes/show')
        ->where('note.views', 7)
        ->where('note.readingMinutes', 1)
        ->where('note.content', fn ($html) => str_contains($html, 'Halo') && ! str_contains($html, '<script')));
});

test('the edit shortcut on a note is only given to blogging staff', function () {
    $note = Note::factory()->create();

    $this->get(route('notes.show', $note))->assertInertia(fn ($page) => $page->where('note.editUrl', null));
    $this->actingAs(User::factory()->create())->get(route('notes.show', $note))->assertInertia(fn ($page) => $page->where('note.editUrl', null));
    $this->actingAs(User::factory()->redaktur()->create())->get(route('notes.show', $note))
        ->assertInertia(fn ($page) => $page->where('note.editUrl', route('admin.notes.edit', $note, absolute: false)));
});

test('draft notes are not public', function () {
    $this->get(route('notes.show', Note::factory()->draft()->create()))->assertNotFound();
});

test('blogging staff manage notes and the author is recorded', function () {
    $tag = Tag::factory()->create();
    $staff = User::factory()->redaktur()->create();

    $this->actingAs(User::factory()->create())->get(route('admin.notes.index'))->assertForbidden();

    $this->actingAs($staff)->post(route('admin.notes.store'), [
        'title' => 'Catatan Baru', 'content' => '<p>Isi</p>', 'is_published' => true, 'published_at' => now()->toDateTimeString(), 'tags' => [$tag->id],
    ])->assertRedirect();

    $note = Note::firstOrFail();
    expect($note)->slug->toBe('catatan-baru')->user_id->toBe($staff->id);
    expect($note->tags)->toHaveCount(1);

    $this->actingAs($staff)->put(route('admin.notes.update', $note), ['title' => 'Diubah', 'content' => '<p>Isi</p>', 'is_published' => false])->assertRedirect();
    expect($note->refresh())->title->toBe('Diubah')->is_published->toBeFalse();
    expect($note->tags)->toHaveCount(0);

    $this->actingAs($staff)->delete(route('admin.notes.destroy', $note))->assertRedirect(route('admin.notes.index'));
    expect(Note::count())->toBe(0);
});

test('a note can be assigned to another author and defaults to the current user', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->redaktur()->create();

    $this->actingAs($admin)->post(route('admin.notes.store'), [
        'title' => 'Milik redaktur', 'content' => '<p>x</p>', 'is_published' => '0', 'user_id' => $other->id,
    ])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.notes.store'), [
        'title' => 'Milik saya', 'content' => '<p>x</p>', 'is_published' => '0',
    ])->assertRedirect();

    expect(Note::query()->where('title', 'Milik redaktur')->value('user_id'))->toBe($other->id)
        ->and(Note::query()->where('title', 'Milik saya')->value('user_id'))->toBe($admin->id);
});

test('a published note is served as markdown with a yaml header', function () {
    $tag = Tag::factory()->create(['name' => 'laravel']);
    $note = Note::factory()->create([
        'title' => 'Catatan: Markdown',
        'content' => '<h2>Judul</h2><p>Halo <strong>dunia</strong><script>alert(1)</script></p><figure><img src="/storage/a.png" alt="A"><figcaption>Keterangan</figcaption></figure>',
    ]);
    $note->tags()->attach($tag);

    $response = $this->get(route('notes.markdown', $note))->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('text/markdown');
    $body = $response->getContent();
    expect($body)->toStartWith("---\n")
        ->toContain("title: 'Catatan: Markdown'")
        ->toContain('- laravel')
        ->toContain('## Judul')
        ->toContain('**dunia**')
        ->toContain(url('/storage/a.png'))
        ->toContain('*Keterangan*')
        ->not->toContain('alert');
});

test('draft notes have no markdown version', function () {
    $this->get(route('notes.markdown', Note::factory()->draft()->create()))->assertNotFound();
});
