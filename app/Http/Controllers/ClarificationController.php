<?php

namespace App\Http\Controllers;

use App\Enums\ClarificationVerdict;
use App\Models\Clarification;
use App\Services\HtmlSanitizer;
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

        if ($clarification->withdrawn_at !== null) {
            return Inertia::render('clarifications/withdrawn', [
                'clarification' => [
                    'title' => $clarification->title,
                    'withdrawnAt' => $clarification->withdrawn_at,
                    'withdrawalNote' => $clarification->withdrawal_note,
                ],
            ]);
        }

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

        return Inertia::render('clarifications/show', ['clarification' => self::detail($clarification)]);
    }

    /**
     * Props of the clarification detail page, shared with the admin preview.
     *
     * @return array<string, mixed>
     */
    public static function detail(Clarification $clarification): array
    {
        return [
            'slug' => $clarification->slug,
            'title' => $clarification->title,
            'claim' => $clarification->claim,
            'claimOrigin' => $clarification->claim_origin,
            'verdict' => $clarification->verdict->value,
            'verdictLabel' => $clarification->verdict->label(),
            'verdictColor' => $clarification->verdict->color(),
            'summary' => $clarification->summary,
            'content' => HtmlSanitizer::clean($clarification->content),
            'apology' => $clarification->apology,
            'coverImage' => ImageService::url($clarification->cover_image),
            'sources' => $clarification->sources ?? [],
            'updates' => collect($clarification->updates ?? [])->sortByDesc('date')->values(),
            'publishedAt' => $clarification->published_at,
            'updatedAt' => $clarification->updated_at,
        ];
    }
}
