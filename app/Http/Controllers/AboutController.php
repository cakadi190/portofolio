<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Portfolio;
use App\Services\ImageService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    /**
     * Show the "about me" page.
     */
    public function me(): Response
    {
        return Inertia::render('tentang/saya', [
            'yearsExperience' => (int) ceil(Carbon::parse('2015-03-17')->diffInYears(now())),
            'yearsServing' => (int) ceil(Carbon::parse('2019-05-20')->diffInYears(now())),
            'totalProjects' => Portfolio::query()->count(),
            'certifications' => Certification::query()
                ->orderByDesc('issued_at')
                ->get()
                ->map(fn (Certification $certification): array => [
                    'id' => $certification->id,
                    'title' => $certification->title,
                    'issuer' => $certification->issuer,
                    'issuedAt' => $certification->issued_at,
                    'expiresAt' => $certification->expires_at,
                    'credentialId' => $certification->credential_id,
                    'credentialUrl' => $certification->credential_url,
                    'file' => ImageService::url($certification->file),
                    'isPdf' => $certification->is_pdf,
                ]),
        ]);
    }

    /**
     * Show the "about this site" page.
     */
    public function site(): Response
    {
        return Inertia::render('tentang/situs');
    }

    /**
     * Show the "skills" page.
     */
    public function skills(): Response
    {
        return Inertia::render('tentang/skill');
    }
}
