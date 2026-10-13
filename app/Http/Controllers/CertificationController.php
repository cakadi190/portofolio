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
     * Show the PDF viewer for a certification whose file is a PDF; images stay in the lightbox on /about.
     */
    public function show(string $slug): Response
    {
        $certification = Certification::findBySlug($slug);

        abort_unless($certification?->is_pdf && $certification->file, 404);

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

        return Inertia::render('certification/show', [
            'title' => $certification->title,
            'description' => "Sertifikat {$certification->title} dari {$certification->issuer}.",
            'pdfUrl' => ImageService::url($certification->file),
            'downloadName' => Str::slug($certification->title).'.pdf',
            'info' => $info,
        ]);
    }
}
