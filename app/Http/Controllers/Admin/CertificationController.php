<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CertificationController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/certifications/index', [
            'certifications' => Certification::query()->orderByDesc('issued_at')->paginate(20),
        ]);
    }

    public function store(CertificationRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $data['file'] = $this->storeCertificate('file');

        Certification::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil ditambahkan.']);

        return to_route('admin.certifications.index');
    }

    public function update(CertificationRequest $request, Certification $certification): RedirectResponse
    {
        $data = $request->safe()->except('file');

        if ($request->hasFile('file')) {
            $this->deleteUpload($certification->file);
            $data['file'] = $this->storeCertificate('file');
        }

        $certification->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil diperbarui.']);

        return to_route('admin.certifications.index');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $this->deleteUpload($certification->file);
        $certification->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil dihapus.']);

        return to_route('admin.certifications.index');
    }

    /**
     * Store an uploaded certificate: PDFs are kept as-is, images are compressed.
     */
    private function storeCertificate(string $field): string
    {
        $file = request()->file($field);

        if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
            return $file->store('certifications', 'public');
        }

        return $this->storeImage($field, 'certifications', 1600, 1600, 400);
    }
}
