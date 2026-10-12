<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Career;
use App\Models\Certification;
use App\Models\Education;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\SpeakingEngagement;
use App\Services\SystemSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ResumeController extends Controller
{
    private const string NAME = 'Amir Zuhdi Wibowo, A.Md.Kom.';

    public function __construct(private SystemSettingService $systemSettings) {}

    /**
     * Show the static PDF viewer pointing at the generated resume.
     */
    public function show(): InertiaResponse
    {
        return Inertia::render('resume/index', [
            'pdfUrl' => route('resume.pdf'),
            'title' => 'CV - '.self::NAME,
        ]);
    }

    /**
     * Stream the ATS-friendly resume PDF built from the portfolio data.
     */
    public function pdf(): Response
    {
        File::ensureDirectoryExists(storage_path('fonts'));

        $pdf = Pdf::loadView('resume', $this->resume())
            ->setPaper('a4')
            ->setOption(['enable_font_subsetting' => true, 'default_font' => 'Signika', 'font_dir' => storage_path('fonts'), 'font_cache' => storage_path('fonts')]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="CV-Amir-Zuhdi-Wibowo.pdf"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * @return array{name: string, headline: string, contacts: list<array{label: string, href: ?string}>, sections: list<array<string, mixed>>}
     */
    private function resume(): array
    {
        $month = fn (?CarbonInterface $date): string => $date ? Carbon::instance($date)->locale('id')->translatedFormat('M Y') : '';
        $period = fn (?CarbonInterface $start, ?CarbonInterface $end): string => $month($start).' – '.($end ? $month($end) : 'Sekarang');
        $city = fn (?string $place): ?string => $place ? collect(explode(',', preg_replace('/\s*\d{4,}\s*$/', '', $place)))->map(fn (string $part): string => trim($part))->take(-2)->implode(', ') : null;

        $portfolios = Portfolio::query()->where('is_private', false)->with('technologies:id,name')->latest()->take(6)->get();
        $years = (int) ceil(Carbon::parse('2015-03-17')->diffInYears(now()));

        $sections = [
            ['title' => 'Ringkasan Profesional', 'entries' => [['lines' => ["Software Engineer dan Wirausahawan berbasis di Kabupaten Ngawi dengan pengalaman {$years} tahun di bidang teknologi, desain, dan pengembangan perangkat lunak. Aktif sebagai pembicara dan mentor."]]]],
            ['title' => 'Pengalaman Kerja', 'entries' => Career::query()->orderByDesc('start_date')->get()->map(fn (Career $c): array => [
                'head' => $c->position,
                'org' => $c->company,
                'period' => $period($c->start_date, $c->end_date),
                'meta' => $c->location,
            ])->all()],
            ['title' => 'Proyek', 'entries' => $portfolios->map(fn (Portfolio $p): array => [
                'head' => $p->name,
                'lines' => array_filter([$p->short_desc]),
                'chips' => $p->technologies->pluck('name')->all(),
                'url' => route('portfolios.show', $p),
                'urlLabel' => preg_replace('#^https?://#', '', route('portfolios.show', $p)),
            ])->all()],
            ['title' => 'Pendidikan', 'entries' => Education::query()->orderByDesc('start_date')->get()->map(fn (Education $e): array => [
                'head' => $e->name,
                'org' => $e->study_program,
                'period' => $period($e->start_date, $e->end_date),
                'meta' => implode(' | ', array_filter([
                    $e->level->label(),
                    $city($e->place),
                    $e->academic_score_value ? ($e->academic_score_label ?: 'Nilai').': '.($e->academic_score_value + 0).($e->academic_score_scale ? ' / '.($e->academic_score_scale + 0) : '') : null,
                ])),
            ])->all()],
            ['title' => 'Sertifikasi', 'entries' => Certification::query()->orderByDesc('issued_at')->get()->map(fn (Certification $c): array => [
                'lines' => ["{$c->title} – {$c->issuer}".($c->issued_at ? ' ('.$month($c->issued_at).')' : '')], 'bullet' => true,
            ])->all()],
            ['title' => 'Penghargaan', 'entries' => Award::query()->orderByDesc('awarded_at')->orderByDesc('year')->get()->map(fn (Award $a): array => [
                'lines' => ["{$a->title}".($a->rank && ! preg_match('/place|juara|peringkat|winner/i', $a->title) ? " (Peringkat {$a->rank})" : '')." – {$a->event_name}, {$a->year}"], 'bullet' => true,
            ])->all()],
            ['title' => 'Pembicara & Mentoring', 'entries' => SpeakingEngagement::query()->published()->orderByDesc('starts_at')->take(10)->get()->map(fn (SpeakingEngagement $s): array => [
                'lines' => ["{$s->title} ({$s->role->label()}) – {$s->organizer}, {$month($s->starts_at)}"], 'bullet' => true,
            ])->all()],
            ['title' => 'Organisasi', 'entries' => Organization::query()->orderByDesc('start_date')->get()->map(fn (Organization $o): array => [
                'head' => $o->name, 'period' => $period($o->start_date, $o->end_date), 'lines' => array_filter([$o->description]),
            ])->all()],
        ];

        return [
            'name' => self::NAME,
            'headline' => 'Software Engineer & Wirausahawan',
            'contacts' => collect([
                ['value' => $this->systemSettings->get('contact_address'), 'link' => false],
                ['value' => $this->systemSettings->get('contact_email'), 'link' => 'mailto:'],
                ['value' => $this->systemSettings->get('contact_website', url('/')), 'link' => ''],
                ['value' => $this->systemSettings->get('social_linkedin'), 'link' => ''],
            ])->filter(fn (array $item): bool => filled($item['value']))->map(fn (array $item): array => [
                'label' => preg_replace('#^https?://(www\.)?#', '', $item['value']),
                'href' => $item['link'] === false ? null : $item['link'].$item['value'],
            ])->values()->all(),
            'sections' => $this->clean(array_values(array_filter($sections, fn (array $s): bool => $s['entries'] !== []))),
        ];
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
