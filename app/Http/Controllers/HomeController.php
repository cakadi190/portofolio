<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\SpeakingEngagement;
use App\Services\ImageService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the public homepage.
     */
    public function index(): Response
    {
        return Inertia::render('app/index', [
            'portfolios' => Portfolio::query()
                ->with(['services:id,name,color', 'technologies:id,name'])
                ->latest()
                ->take(3)
                ->get()
                ->map(fn (Portfolio $portfolio): array => [
                    'name' => $portfolio->name,
                    'slug' => $portfolio->slug,
                    'image' => ImageService::url($portfolio->image),
                    'shortDesc' => $portfolio->short_desc,
                    'services' => $portfolio->services->map(fn ($service): array => [
                        'name' => $service->name,
                        'color' => $service->color,
                    ]),
                    'technologies' => $portfolio->technologies->pluck('name'),
                ]),
            'speakings' => $this->speakings(),
            'posts' => Post::query()
                ->where('is_published', true)
                ->with(['categories:id,name,color', 'tags:id,name', 'author:id,name'])
                ->latest('published_at')
                ->take(3)
                ->get()
                ->map(fn (Post $post): array => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'coverImage' => ImageService::url($post->cover_image),
                    'author' => $post->author?->name,
                    'categories' => $post->categories->map(fn ($category): array => [
                        'name' => $category->name,
                        'color' => $category->color,
                    ]),
                    'tags' => $post->tags->pluck('name'),
                ]),
        ]);
    }

    /**
     * Up to three published engagements for the homepage: upcoming ones
     * (nearest first), topped up with the most recent past ones.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function speakings(): Collection
    {
        $upcoming = SpeakingEngagement::query()
            ->published()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(3)
            ->get();

        $past = SpeakingEngagement::query()
            ->published()
            ->where('starts_at', '<', now())
            ->orderByDesc('starts_at')
            ->take(3 - $upcoming->count())
            ->get();

        return $upcoming->concat($past)->map(fn (SpeakingEngagement $engagement): array => [
            'id' => $engagement->id,
            'title' => $engagement->title,
            'organizer' => $engagement->organizer,
            'roleLabel' => $engagement->role->label(),
            'formatLabel' => $engagement->format->label(),
            'location' => $engagement->location,
            'startsAt' => $engagement->starts_at->format('Y-m-d H:i'),
            'poster' => ImageService::url($engagement->poster),
        ]);
    }
}
