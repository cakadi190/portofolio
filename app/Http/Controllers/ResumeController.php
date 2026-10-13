<?php

namespace App\Http\Controllers;

use App\Enums\ResumeProfile;
use App\Services\ResumeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class ResumeController extends Controller
{
    public function __construct(private ResumeService $resume) {}

    /**
     * Show the PDF viewer for a CV profile (the default profile when none is given).
     */
    public function show(?ResumeProfile $profile = null): InertiaResponse
    {
        $profile ??= ResumeProfile::default();

        return Inertia::render('resume/index', [
            'profile' => $profile->value,
            'profiles' => collect(ResumeProfile::cases())->map(fn (ResumeProfile $item): array => [
                'key' => $item->value,
                'label' => $item->label(),
                'description' => $item->description(),
                'url' => route('resume.profile', $item),
                'pdfUrl' => $this->pdfUrl($item),
                'downloadName' => $item->filename(),
            ])->all(),
            'pdfUrl' => $this->pdfUrl($profile),
            'downloadName' => $profile->filename(),
            'title' => 'Resume / Curriculum Vitae',
            'description' => "{$profile->label()} {$profile->headline()}: ".$profile->description(),
        ]);
    }

    /**
     * Stream the ATS-friendly resume PDF of a profile, built from the same data as the web CV.
     */
    public function pdf(?ResumeProfile $profile = null): Response
    {
        $profile ??= ResumeProfile::default();

        try {
            File::ensureDirectoryExists(storage_path('fonts'));

            $output = Pdf::loadView('resume', $this->resume->build($profile))
                ->setPaper('a4')
                ->setOption(['enable_font_subsetting' => true, 'default_font' => 'Signika', 'font_dir' => storage_path('fonts'), 'font_cache' => storage_path('fonts')])
                ->output();
        } catch (Throwable $exception) {
            Log::error('Resume PDF generation failed', ['profile' => $profile->value, 'exception' => $exception]);

            abort(503, 'CV sementara tidak dapat dibuat. Coba lagi beberapa saat.');
        }

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$profile->filename().'"',
            'Cache-Control' => 'no-cache, private',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function pdfUrl(ResumeProfile $profile): string
    {
        return $profile === ResumeProfile::default() ? route('resume.pdf') : route('resume.profile.pdf', $profile);
    }
}
