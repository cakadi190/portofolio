<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Post;
use App\Services\ImageService;
use Illuminate\Http\Request;

trait PresentsBlogPosts
{
    /**
     * Relations (with only the columns a card needs) to eager load before {@see self::postCard()}.
     *
     * @return list<string>
     */
    protected function cardRelations(): array
    {
        return ['categories:id,name,slug,color', 'tags:id,name,slug', 'author:id,name,slug'];
    }

    /**
     * The edit shortcut is only generated for staff allowed to manage the blog, so
     * neither the URL nor the post id reaches anyone else. The editor route itself
     * is guarded by the same `manage-blog` gate.
     */
    protected function canEditPosts(Request $request): bool
    {
        return $request->user()?->can('manage-blog') === true;
    }

    /**
     * Public card payload of a post whose {@see self::cardRelations()} are loaded.
     *
     * @return array<string, mixed>
     */
    protected function postCard(Post $post, bool $canEdit = false): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'coverImage' => ImageService::thumbnailUrl($post->cover_image),
            'publishedAt' => $post->published_at,
            'author' => $post->author ? ['name' => $post->author->name, 'slug' => $post->author->slug, 'avatar' => $post->author->avatar] : null,
            'categories' => $post->categories->map(fn ($category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
            ])->values(),
            'tags' => $post->tags->map(fn ($tag): array => ['name' => $tag->name, 'slug' => $tag->slug])->values(),
            'editUrl' => $canEdit ? route('admin.posts.edit', $post, absolute: false) : null,
        ];
    }
}
