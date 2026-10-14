<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PresentsBlogPosts;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\User;
use App\Services\ContentImageOptimizer;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\SeoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public archive pages of the blog: posts of one category, tag or author.
 * Only published posts are listed or counted.
 */
class BlogTaxonomyController extends Controller
{
    use PresentsBlogPosts;

    private const PER_PAGE = 9;

    public function category(Request $request, PostCategory $category, SeoService $seo): Response
    {
        $posts = $this->posts($request, $category->posts());
        $description = "Kumpulan {$posts->total()} artikel dalam kategori {$category->name}.";

        $this->describe($seo, "Kategori {$category->name}", $description, route('blog.categories.show', $category), $category->name);

        return Inertia::render('blog/category', [
            'category' => ['name' => $category->name, 'slug' => $category->slug, 'color' => $category->color],
            'posts' => Inertia::scroll($posts),
            'totalPosts' => $posts->total(),
            'relatedCategories' => $this->relatedCategories($category),
            'relatedTags' => $this->relatedTags(fn (Builder $query) => $query->whereHas('categories', fn ($categories) => $categories->whereKey($category->id))),
        ]);
    }

    public function tag(Request $request, Tag $tag, SeoService $seo): Response
    {
        $posts = $this->posts($request, $tag->posts());
        $description = "Kumpulan {$posts->total()} artikel dengan tag {$tag->name}.";

        $this->describe($seo, "Tag {$tag->name}", $description, route('blog.tags.show', $tag), $tag->name);

        return Inertia::render('blog/tag', [
            'tag' => ['name' => $tag->name, 'slug' => $tag->slug],
            'posts' => Inertia::scroll($posts),
            'totalPosts' => $posts->total(),
            'relatedTags' => $this->relatedTags(fn (Builder $query) => $query->whereHas('tags', fn ($tags) => $tags->whereKey($tag->id)), $tag->id),
        ]);
    }

    /**
     * Account holders are only public authors when they write for the blog:
     * staff, or anyone with a published post. Any other slug is a 404, so
     * regular accounts (e.g. commenters) are never exposed.
     */
    public function author(Request $request, User $author, SeoService $seo): Response
    {
        abort_unless(
            $author->canManageBlog() || $author->authoredPosts()->published()->exists(),
            404,
        );

        $posts = $this->posts($request, $author->authoredPosts());
        $description = "Profil penulis {$author->name} dengan {$posts->total()} artikel yang diterbitkan.";
        $url = route('blog.authors.show', $author);

        $this->describe($seo, "Penulis {$author->name}", $description, $url, $author->name, [
            'type' => 'profile',
            'image' => ImageService::url($author->avatar),
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'url' => $url,
                'inLanguage' => 'id-ID',
                'mainEntity' => array_filter([
                    '@type' => 'Person',
                    'name' => $author->name,
                    'url' => $url,
                    'image' => ($avatar = ImageService::url($author->avatar)) ? url($avatar) : null,
                ]),
            ]],
        ]);

        return Inertia::render('blog/author', [
            'author' => [
                'name' => $author->name,
                'username' => $author->username,
                'avatar' => ImageService::url($author->avatar),
                'bio' => $author->bio ? ContentImageOptimizer::optimize(HtmlSanitizer::clean($author->bio)) : null,
            ],
            'posts' => Inertia::scroll($posts),
            'totalPosts' => $posts->total(),
        ]);
    }

    /**
     * One page of published posts of the given relation, newest first.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<Post, *, *>  $relation
     */
    private function posts(Request $request, $relation): LengthAwarePaginator
    {
        $canEdit = $this->canEditPosts($request);

        return $relation
            ->published()
            ->with($this->cardRelations())
            ->latest('published_at')
            ->latest('posts.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->postCard($post, $canEdit));
    }

    /**
     * Other categories that have published posts, with their counts.
     *
     * @return list<array{name: string, slug: string, color: string|null, count: int}>
     */
    private function relatedCategories(PostCategory $current): array
    {
        return PostCategory::query()
            ->whereKeyNot($current->id)
            ->whereHas('posts', fn ($query) => $query->published())
            ->withCount(['posts' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get()
            ->map(fn (PostCategory $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
                'count' => $category->posts_count,
            ])
            ->all();
    }

    /**
     * Tags used by published posts that match the given constraint.
     *
     * @param  callable(Builder<Post>): mixed  $constrainPosts
     * @return list<array{name: string, slug: string, count: int}>
     */
    private function relatedTags(callable $constrainPosts, ?int $exceptId = null): array
    {
        return Tag::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereHas('posts', fn ($posts) => $posts->published()->tap($constrainPosts))
            ->withCount(['posts' => fn ($posts) => $posts->published()->tap($constrainPosts)])
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Tag $tag): array => ['name' => $tag->name, 'slug' => $tag->slug, 'count' => $tag->posts_count])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function describe(SeoService $seo, string $title, string $description, string $url, string $name, array $extra = []): void
    {
        $seo->set([
            'title' => $title,
            'description' => Str::limit($description, 200),
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'url' => $url,
                'inLanguage' => 'id-ID',
            ]],
            ...$extra,
        ]);
    }
}
