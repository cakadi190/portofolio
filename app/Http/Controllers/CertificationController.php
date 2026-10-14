<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Services\ImageService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CertificationController extends Controller
{
    /**
     * Show a certification: PDFs open in the document viewer, images in a plain detail page.
     */
    public function show(string $slug): Response
    {
        $certification = Certification::findBySlug($slug);

        abort_unless($certification?->file, 404);

        $info = [
            ['label' => 'Nama sertifikat', 'value' => $certification->title],
            ['label' => 'Penerbit', 'value' => $certification->issuer],
            ['label' => 'Terbit', 'value' => $certification->issued_at->translatedFormat('j F Y')],
        ];

        if ($certification->expires_at) {
            $info[] = ['label' => 'Berlaku hingga', 'value' => $certification->expires_at->translatedFormat('j F Y')];
        }

        if ($certification->credential_id) {
            $info[] = ['label' => 'ID kredensial', 'value' => $certification->credential_id];
        }

        if ($certification->credential_url) {
            $info[] = ['label' => 'Verifikasi kredensial', 'value' => 'Buka tautan', 'href' => $certification->credential_url];
        }

        if (! $certification->is_pdf) {
            return Inertia::render('certification/detail', [
                'title' => $certification->title,
                'description' => "Sertifikat {$certification->title} dari {$certification->issuer}.",
                'imageUrl' => ImageService::url($certification->file),
                'info' => $info,
            ]);
        }

        request()->attributes->set('pdfViewer', true);

        return Inertia::render('certification/show', [
            'title' => $certification->title,
            'description' => "Sertifikat {$certification->title} dari {$certification->issuer}.",
            'pdfUrl' => ImageService::url($certification->file),
            'downloadName' => Str::slug($certification->title).'.pdf',
            'info' => $info,
        ]);
    }
}
