<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Post;
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
                ->with(['categories:id,name,color', 'technologies:id,name'])
                ->latest()
                ->take(3)
                ->get()
                ->map(fn (Portfolio $portfolio): array => [
                    'name' => $portfolio->name,
                    'slug' => $portfolio->slug,
                    'image' => $portfolio->image,
                    'shortDesc' => $portfolio->short_desc,
                    'categories' => $portfolio->categories->map(fn ($category): array => [
                        'name' => $category->name,
                        'color' => $category->color,
                    ]),
                    'technologies' => $portfolio->technologies->pluck('name'),
                ]),
            'posts' => Post::query()
                ->where('is_published', true)
                ->with(['categories:id,name,color', 'tags:id,name'])
                ->latest('published_at')
                ->take(3)
                ->get()
                ->map(fn (Post $post): array => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'coverImage' => $post->cover_image,
                    'categories' => $post->categories->map(fn ($category): array => [
                        'name' => $category->name,
                        'color' => $category->color,
                    ]),
                    'tags' => $post->tags->pluck('name'),
                ]),
        ]);
    }
}
