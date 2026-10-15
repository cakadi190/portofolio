<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NestsReplies;
use App\Http\Controllers\Concerns\PresentsBlogPosts;
use App\Models\BlogComment;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\ContentImageOptimizer;
use App\Services\HtmlSanitizer;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    use NestsReplies, PresentsBlogPosts;

    private const int COMMENT_ROOTS_PER_PAGE = 10;

    /**
     * Show the paginated list of published posts.
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->integer('category') ?: null;
        $canEdit = $this->canEditPosts($request);

        $posts = Post::query()
            ->where('is_published', true)
            ->when($categoryId, fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->whereKey($categoryId)))
            ->with($this->cardRelations())
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Post $post): array => $this->postCard($post, $canEdit));

        return Inertia::render('blog/index', [
            'posts' => Inertia::scroll($posts),
            'activeCategory' => $categoryId ? PostCategory::query()->find($categoryId, ['id', 'name']) : null,
        ]);
    }

    /**
     * Show a single published post.
     */
    public function show(Request $request, Post $post, SeoService $seo): Response
    {
        $isPreview = ! $post->is_published && $this->canPreview($request, $post);

        abort_unless($post->is_published || $isPreview, 404);

        $post->load($this->cardRelations());

        $cover = ContentImageOptimizer::cover($post->cover_image);
        $coverImage = $cover['src'];

        $seo->set([
            ...($isPreview ? ['robots' => 'noindex,nofollow'] : []),
            'title' => $post->title,
            'author' => $post->author?->name,
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
                ...$this->postCard($post, $this->canEditPosts($request)),
                'coverImage' => $cover['src'],
                'coverImageSrcset' => $cover['srcset'],
                'coverImageSize' => $cover['width'] ? ['width' => $cover['width'], 'height' => $cover['height']] : null,
                'content' => ContentImageOptimizer::optimize(HtmlSanitizer::clean($post->content)),
            ],
            'comments' => Inertia::scroll(fn () => $this->commentPage($post)),
            'totalComments' => $post->comments()->approved()->count(),
            'otherPosts' => $this->otherPosts($post),
            'categories' => $this->categoryList(),
            'adjacent' => $this->adjacentPosts($post),
        ]);
    }

    /**
     * A draft is only viewable through its signed preview URL, by staff who manage the blog.
     */
    private function canPreview(Request $request, Post $post): bool
    {
        return $request->query('action') === 'preview'
            && $request->user()?->canManageBlog() === true
            && hash_equals($post->previewHash(), (string) $request->query('cache'));
    }

    /**
     * The five newest published posts other than the one being read.
     *
     * @return Collection<int, array{title: string, slug: string, publishedAt: mixed}>
     */
    private function otherPosts(Post $post): Collection
    {
        return Post::query()
            ->where('is_published', true)
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'published_at'])
            ->map(fn (Post $other): array => $this->postLink($other, withDate: true))
            ->values();
    }

    /**
     * Every category that has at least one published post, with that count.
     *
     * @return Collection<int, array{id: int, name: string, slug: string, color: string|null, count: int}>
     */
    private function categoryList(): Collection
    {
        return PostCategory::query()
            ->whereHas('posts', fn ($query) => $query->where('is_published', true))
            ->withCount(['posts' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (PostCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
                'count' => $category->posts_count,
            ])
            ->values();
    }

    /**
     * The next older (`previous`) and newer (`next`) published posts, ordered by publish date.
     *
     * @return array{previous: array<string, mixed>|null, next: array<string, mixed>|null}
     */
    private function adjacentPosts(Post $post): array
    {
        $published = Post::query()->where('is_published', true);
        $date = $post->published_at;

        if ($date === null) {
            return ['previous' => null, 'next' => null];
        }

        $older = (clone $published)
            ->where(fn ($query) => $query
                ->where('published_at', '<', $date)
                ->orWhere(fn ($tie) => $tie->where('published_at', $date)->where('id', '<', $post->id)))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->first(['id', 'title', 'slug']);

        $newer = (clone $published)
            ->where(fn ($query) => $query
                ->where('published_at', '>', $date)
                ->orWhere(fn ($tie) => $tie->where('published_at', $date)->where('id', '>', $post->id)))
            ->orderBy('published_at')->orderBy('id')
            ->first(['id', 'title', 'slug']);

        return [
            'previous' => $older ? $this->postLink($older) : null,
            'next' => $newer ? $this->postLink($newer) : null,
        ];
    }

    /**
     * @return array{title: string, slug: string, publishedAt?: mixed}
     */
    private function postLink(Post $post, bool $withDate = false): array
    {
        return ['title' => $post->title, 'slug' => $post->slug] + ($withDate ? ['publishedAt' => $post->published_at] : []);
    }

    /**
     * One page of approved root comments with their approved reply trees. A
     * reply whose ancestor is not approved is hidden together with that ancestor.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function commentPage(Post $post): LengthAwarePaginator
    {
        $roots = $post->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with('user:id,name,avatar')
            ->oldest()
            ->paginate(self::COMMENT_ROOTS_PER_PAGE);

        $roots->setCollection($this->nestReplies(
            $roots->getCollection(),
            fn (Collection $parentIds): Collection => $post->comments()
                ->approved()
                ->with('user:id,name,avatar')
                ->whereIn('parent_id', $parentIds)
                ->oldest()
                ->get(),
            fn (BlogComment $comment, Collection $replies): array => [
                'id' => $comment->id,
                'body' => HtmlSanitizer::richText($comment->body),
                'author' => $comment->user?->name ?? $comment->guest_name ?? 'Pengguna terhapus',
                'authorAvatar' => $comment->user?->avatar,
                'createdAt' => $comment->created_at,
                'replies' => $replies,
            ],
        ));

        return $roots;
    }
}
