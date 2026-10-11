<?php

namespace App\Http\Controllers;

use App\Enums\ClarificationVerdict;
use App\Models\Clarification;
use App\Services\ImageService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ClarificationController extends Controller
{
    /**
     * Show the paginated list of published clarifications, optionally by verdict.
     */
    public function index(Request $request): Response
    {
        $verdict = ClarificationVerdict::tryFrom((string) $request->query('verdict'));

        $clarifications = Clarification::query()
            ->published()
            ->when($verdict, fn ($query) => $query->where('verdict', $verdict))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Clarification $clarification): array => [
                'title' => $clarification->title,
                'slug' => $clarification->slug,
                'claim' => $clarification->claim,
                'summary' => $clarification->summary,
                'coverImage' => ImageService::url($clarification->cover_image),
                'verdict' => $clarification->verdict->value,
                'verdictLabel' => $clarification->verdict->label(),
                'verdictColor' => $clarification->verdict->color(),
                'publishedAt' => $clarification->published_at,
            ]);

        return Inertia::render('clarifications/index', [
            'clarifications' => $clarifications,
            'verdicts' => ClarificationVerdict::options(),
            'activeVerdict' => $verdict?->value,
        ]);
    }

    /**
     * Show a single published clarification.
     */
    public function show(Clarification $clarification, SeoService $seo): Response
    {
        abort_unless($clarification->is_published && $clarification->published_at?->isPast(), 404);

        $coverImage = ImageService::url($clarification->cover_image);
        $description = $clarification->summary ?: Str::limit(strip_tags($clarification->content), 200);

        $seo->set([
            'title' => $clarification->title,
            'description' => $description,
            'image' => $coverImage,
            'image_alt' => $clarification->title,
            'type' => 'article',
            'published_time' => $clarification->published_at->toAtomString(),
            'modified_time' => $clarification->updated_at?->toAtomString(),
            'section' => 'Klarifikasi',
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $clarification->title,
                'description' => $description,
                'image' => $coverImage ? [url($coverImage)] : [url(config('seo.image'))],
                'datePublished' => $clarification->published_at->toAtomString(),
                'dateModified' => $clarification->updated_at?->toAtomString(),
                'inLanguage' => 'id-ID',
                'mainEntityOfPage' => route('clarifications.show', $clarification),
                'articleSection' => 'Klarifikasi',
                'author' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
                'publisher' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            ]],
        ]);

        return Inertia::render('clarifications/show', [
            'clarification' => [
                'slug' => $clarification->slug,
                'title' => $clarification->title,
                'claim' => $clarification->claim,
                'claimOrigin' => $clarification->claim_origin,
                'verdict' => $clarification->verdict->value,
                'verdictLabel' => $clarification->verdict->label(),
                'verdictColor' => $clarification->verdict->color(),
                'summary' => $clarification->summary,
                'content' => $clarification->content,
                'apology' => $clarification->apology,
                'coverImage' => $coverImage,
                'sources' => $clarification->sources ?? [],
                'updates' => collect($clarification->updates ?? [])->sortByDesc('date')->values(),
                'publishedAt' => $clarification->published_at,
                'updatedAt' => $clarification->updated_at,
            ],
        ]);
    }
}
