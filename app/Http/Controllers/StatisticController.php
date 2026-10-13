<?php

namespace App\Http\Controllers;

use App\Enums\BlogCommentStatus;
use App\Models\GuestbookEntry;
use App\Models\Note;
use App\Models\Post;
use App\Services\Visitors\PageViewCounter;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    public function __construct(private readonly PageViewCounter $views) {}

    /**
     * Show page view counters for the whole site, the blog and the notes.
     */
    public function index(): Response
    {
        $posts = $this->rows(Post::query()->where('is_published', true)->latest('published_at')->get(['title', 'slug']), 'blog');
        $notes = $this->rows(Note::query()->published()->latest('published_at')->get(['title', 'slug']), 'notes');

        return Inertia::render('statistic/index', [
            'siteViews' => $this->views->total(),
            'blog' => ['count' => $posts->count(), 'views' => $posts->sum('views'), 'rows' => $posts->values()],
            'notes' => ['count' => $notes->count(), 'views' => $notes->sum('views'), 'rows' => $notes->values()],
            'guestbookCount' => GuestbookEntry::query()->where('status', BlogCommentStatus::Approved)->count(),
        ]);
    }

    /**
     * @param  Collection<int, Post|Note>  $items
     * @return Collection<int, array{title: string, slug: string, views: int}>
     */
    private function rows(Collection $items, string $prefix): Collection
    {
        $views = $this->views->forPaths($items->map(fn ($item): string => "/{$prefix}/{$item->slug}")->all());

        return $items
            ->map(fn ($item): array => ['title' => $item->title, 'slug' => $item->slug, 'views' => $views["/{$prefix}/{$item->slug}"] ?? 0])
            ->sortByDesc('views')
            ->values();
    }
}
