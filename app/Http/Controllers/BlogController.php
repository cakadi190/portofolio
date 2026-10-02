<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\ImageService;
use App\Services\SeoService;
use Illuminate\Support\Str;
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
    public function show(Post $post, SeoService $seo): Response
    {
        abort_unless($post->is_published, 404);

        $post->load(['categories:id,name,color', 'tags:id,name']);

        $coverImage = ImageService::url($post->cover_image);

        $seo->set([
            'title' => $post->title,
            'description' => $post->excerpt ?: Str::limit(strip_tags($post->content), 200),
            'image' => $coverImage,
            'image_alt' => $post->title,
            'type' => 'article',
            'published_time' => $post->published_at?->toAtomString(),
            'modified_time' => $post->updated_at?->toAtomString(),
            'section' => $post->categories->first()?->name,
            'tags' => $post->tags->pluck('name')->all(),
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'description' => $post->excerpt ?: Str::limit(strip_tags($post->content), 200),
                'image' => $coverImage ? [url($coverImage)] : [url(config('seo.image'))],
                'datePublished' => $post->published_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'inLanguage' => 'id-ID',
                'mainEntityOfPage' => route('blog.show', $post),
                'keywords' => $post->tags->pluck('name')->implode(', '),
                'author' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
                'publisher' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            ]],
        ]);

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
