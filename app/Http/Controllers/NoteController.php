<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Services\ContentImageOptimizer;
use App\Services\HtmlSanitizer;
use App\Services\NoteMarkdownExporter;
use App\Services\SeoService;
use App\Services\Visitors\PageViewCounter;
use App\Support\AppLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(private readonly PageViewCounter $views) {}

    /**
     * Show the published notes, optionally narrowed to one tag.
     */
    public function index(Request $request): Response
    {
        $activeTag = $request->query('tag');
        $activeTag = is_string($activeTag) && $activeTag !== '' ? $activeTag : null;

        $notes = Note::query()
            ->published()
            ->with('tags:id,name,name_en')
            ->when($activeTag, fn ($query) => $query->whereHas('tags', fn ($tags) => $tags->where('name', $activeTag)))
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        $views = $this->views->forPaths($notes->getCollection()->map(fn (Note $note): string => "/notes/{$note->slug}")->all());

        return Inertia::render('notes/index', [
            'notes' => Inertia::scroll($notes->through(fn (Note $note): array => [
                'title' => $note->title,
                'slug' => $note->slug,
                'excerpt' => $note->excerpt,
                'publishedAt' => $note->published_at,
                'views' => $views["/notes/{$note->slug}"] ?? 0,
                'tags' => $note->tags->pluck('name'),
            ])),
            'tags' => Tag::query()
                ->whereHas('notes', fn ($query) => $query->published())
                ->orderBy('name')
                ->pluck('name'),
            'activeTag' => $activeTag,
        ]);
    }

    /**
     * Show a single published note.
     */
    public function show(Request $request, Note $note, SeoService $seo): Response
    {
        abort_unless($note->is_published, 404);

        $note->load('tags:id,name,name_en');

        $description = $note->excerpt ?: Str::limit(strip_tags($note->content), 200);

        $seo->set([
            'locales' => $note->translatedLocales(),
            'title' => $note->title,
            'description' => $description,
            'type' => 'article',
            'published_time' => $note->published_at?->toAtomString(),
            'modified_time' => $note->updated_at?->toAtomString(),
            'tags' => $note->tags->pluck('name')->all(),
            'breadcrumbs' => [
                ['name' => 'Catatan Singkat', 'url' => route('notes.index')],
                ['name' => $note->title, 'url' => route('notes.show', $note)],
            ],
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $note->title,
                'description' => $description,
                'datePublished' => $note->published_at?->toAtomString(),
                'dateModified' => $note->updated_at?->toAtomString(),
                'inLanguage' => AppLocale::languageTag(),
                'mainEntityOfPage' => route('notes.show', $note),
                'keywords' => $note->tags->pluck('name')->implode(', '),
                'author' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
                'publisher' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            ]],
        ]);

        return Inertia::render('notes/show', [
            'note' => [
                'slug' => $note->slug,
                'title' => $note->title,
                'excerpt' => $note->excerpt,
                'content' => ContentImageOptimizer::optimize(HtmlSanitizer::clean($note->content)),
                'publishedAt' => $note->published_at,
                'readingMinutes' => $note->readingMinutes(),
                'views' => $this->views->forPath("/notes/{$note->slug}"),
                'tags' => $note->tags->pluck('name'),
                'editUrl' => $request->user()?->can('manage-blog') === true
                    ? route('admin.notes.edit', $note, absolute: false)
                    : null,
            ],
        ]);
    }

    /**
     * Serve a published note as Markdown with a YAML front-matter header.
     */
    public function markdown(Note $note, NoteMarkdownExporter $exporter): HttpResponse
    {
        abort_unless($note->is_published, 404);

        return response($exporter->export($note), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
