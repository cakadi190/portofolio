<?php

use App\Models\Note;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

test('the statistic page totals human views for the site, blog and notes', function () {
    $post = Post::factory()->create();
    $note = Note::factory()->create();
    Note::factory()->draft()->create();

    DB::table('visitor_daily_stats')->insert([
        ['day' => today(), 'path' => '/', 'is_bot' => false, 'page_views' => 10],
        ['day' => today(), 'path' => "/blog/{$post->slug}", 'is_bot' => false, 'page_views' => 4],
        ['day' => today(), 'path' => "/notes/{$note->slug}", 'is_bot' => false, 'page_views' => 6],
        ['day' => today(), 'path' => "/notes/{$note->slug}", 'is_bot' => true, 'page_views' => 50],
    ]);

    $this->get(route('statistic.index'))->assertInertia(fn ($page) => $page
        ->component('statistic/index')
        ->where('siteViews', 20)
        ->where('blog.views', 4)
        ->where('blog.count', 1)
        ->where('notes.views', 6)
        ->where('notes.count', 1)
        ->where('notes.rows.0.slug', $note->slug));
});

test('the legacy statistics url redirects to the statistic page', function () {
    $this->get('/statistics')->assertRedirect('/id/statistic')->assertStatus(301);
});
