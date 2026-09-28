<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ImageService;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Show the paginated list of published posts.
     */
    public function index(): Response
    {
        $posts = Post::query()
            ->where('is_published', true)
            ->with('categories:id,name,color')
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Post $post): array => [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'coverImage' => ImageService::url($post->cover_image),
                'categories' => $post->categories->map(fn ($category): array => [
                    'name' => $category->name,
                    'color' => $category->color,
                ]),
            ]);

        return Inertia::render('blog/index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Show a single published post.
     */
    public function show(Post $post): Response
    {
        $post->load(['categories:id,name,color', 'tags:id,name']);

        return Inertia::render('blog/show', [
            'post' => [
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'coverImage' => ImageService::url($post->cover_image),
                'publishedAt' => $post->published_at,
                'categories' => $post->categories->map(fn ($category): array => [
                    'name' => $category->name,
                    'color' => $category->color,
                ]),
                'tags' => $post->tags->pluck('name'),
            ],
        ]);
    }
}
