<?php

namespace App\Services;

use App\Enums\ResumeProfile;
use App\Models\Award;
use App\Models\Career;
use App\Models\Certification;
use App\Models\Education;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\SpeakingEngagement;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Single source of CV content. Every profile reads the same database records;
 * a ResumeProfile only selects, orders and limits sections.
 */
class ResumeService
{
    public const string NAME = 'Amir Zuhdi Wibowo, A.Md.Kom.';

    public function __construct(private SystemSettingService $systemSettings) {}

    /**
     * @return array{name: string, headline: string, profile: string, contacts: list<array{label: string, href: ?string}>, sections: list<array<string, mixed>>}
     */
    public function build(ResumeProfile $profile): array
    {
        $available = $this->sections($profile);

        $sections = collect($profile->sections())
            ->map(fn (string $key): ?array => isset($available[$key]) ? ['key' => $key, ...$available[$key]] : null)
            ->filter(fn (?array $section): bool => $section !== null && $section['entries'] !== [])
            ->values()
            ->all();

        return [
            'name' => self::NAME,
            'headline' => $profile->headline(),
            'profile' => $profile->value,
            'contacts' => $this->contacts(),
            'sections' => $this->clean($sections),
        ];
    }

    /**
     * @return array<string, array{title: string, entries: list<array<string, mixed>>}>
     */
    private function sections(ResumeProfile $profile): array
    {
        $month = fn (?CarbonInterface $date): string => $date ? Carbon::instance($date)->locale(app()->getLocale())->translatedFormat('M Y') : '';
        $period = fn (?CarbonInterface $start, ?CarbonInterface $end): string => $month($start).' – '.($end ? $month($end) : __('resume.present'));
        $city = fn (?string $place): ?string => $place ? collect(explode(',', preg_replace('/\s*\d{4,}\s*$/', '', $place)))->map(fn (string $part): string => trim($part))->take(-2)->implode(', ') : null;

        $portfolios = Portfolio::query()->where('is_private', false)->with('technologies:id,name')->latest()->get();
        $years = (int) ceil(Carbon::parse('2015-03-17')->diffInYears(now()));

        return [
            'summary' => ['title' => __('resume.summary'), 'entries' => [['lines' => [__('resume.summary_text', ['years' => $years])]]]],
            'skills' => ['title' => __('resume.skills'), 'entries' => [[
                'chips' => $portfolios->flatMap(fn (Portfolio $p) => $p->technologies->pluck('name'))->unique()->sort()->values()->all(),
            ]]],
            'career' => ['title' => __('resume.career'), 'entries' => Career::query()->orderByDesc('start_date')->get()->map(fn (Career $c): array => [
                'head' => $c->position,
                'org' => $c->company,
                'period' => $period($c->start_date, $c->end_date),
                'meta' => $c->location,
            ])->all()],
            'projects' => ['title' => __('resume.projects'), 'entries' => $portfolios->take($profile->projectLimit())->map(fn (Portfolio $p): array => [
                'head' => $p->name,
                'lines' => array_filter([$p->short_desc]),
                'chips' => $profile->showsProjectTechnologies() ? $p->technologies->pluck('name')->all() : [],
                'url' => route('portfolios.show', $p),
                'urlLabel' => preg_replace('#^https?://#', '', route('portfolios.show', $p)),
            ])->values()->all()],
            'education' => ['title' => __('resume.education'), 'entries' => Education::query()->orderByDesc('start_date')->get()->map(fn (Education $e): array => [
                'head' => $e->name,
                'org' => $e->study_program,
                'period' => $period($e->start_date, $e->end_date),
                'meta' => implode(' | ', array_filter([
                    $e->level->label(),
                    $city($e->place),
                    $e->academic_score_value ? ($e->academic_score_label ?: __('resume.score')).': '.($e->academic_score_value + 0).($e->academic_score_scale ? ' / '.($e->academic_score_scale + 0) : '') : null,
                ])),
            ])->all()],
            'certifications' => ['title' => __('resume.certifications'), 'entries' => Certification::query()->orderByDesc('issued_at')->get()->map(fn (Certification $c): array => [
                'lines' => ["{$c->title} – {$c->issuer}".($c->issued_at ? ' ('.$month($c->issued_at).')' : '')], 'bullet' => true,
            ])->all()],
            'awards' => ['title' => __('resume.awards'), 'entries' => Award::query()->orderByDesc('awarded_at')->orderByDesc('year')->get()->map(fn (Award $a): array => [
                'lines' => ["{$a->title}".($a->rank && ! preg_match('/place|juara|peringkat|winner/i', $a->title) ? ' ('.__('resume.rank', ['rank' => $a->rank]).')' : '')." – {$a->event_name}, {$a->year}"], 'bullet' => true,
            ])->all()],
            'speaking' => ['title' => __('resume.speaking'), 'entries' => SpeakingEngagement::query()->published()->orderByDesc('starts_at')->take(10)->get()->map(fn (SpeakingEngagement $s): array => [
                'lines' => ["{$s->title} ({$s->role->label()}) – {$s->organizer}, {$month($s->starts_at)}"], 'bullet' => true,
            ])->all()],
            'organizations' => ['title' => __('resume.organizations'), 'entries' => Organization::query()->orderByDesc('start_date')->get()->map(fn (Organization $o): array => [
                'head' => $o->name, 'period' => $period($o->start_date, $o->end_date), 'lines' => array_filter([$o->description]),
            ])->all()],
        ];
    }

    /**
     * Public contact details only (system settings intended for display).
     *
     * @return list<array{label: string, href: ?string}>
     */
    private function contacts(): array
    {
        return collect([
            ['value' => $this->systemSettings->get('contact_address'), 'link' => false],
            ['value' => $this->systemSettings->get('contact_email'), 'link' => 'mailto:'],
            ['value' => $this->systemSettings->get('contact_website', url('/')), 'link' => ''],
            ['value' => $this->systemSettings->get('social_linkedin'), 'link' => ''],
        ])->filter(fn (array $item): bool => filled($item['value']))->map(fn (array $item): array => [
            'label' => preg_replace('#^https?://(www\.)?#', '', $item['value']),
            'href' => $item['link'] === false ? null : $item['link'].$item['value'],
        ])->values()->all();
    }

    /**
     * Strip emoji, private-use and zero-width characters the PDF fonts cannot draw, and normalise spaces.
     *
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function clean(array $data): array
    {
        array_walk_recursive($data, function (mixed &$value): void {
            if (is_string($value)) {
                $value = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x{10000}-\x{10FFFF}\x{E000}-\x{F8FF}\x{200B}-\x{200F}\x{FE0E}\x{FE0F}]/u', '', $value)));
            }
        });

        return $data;
    }
}
