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
