<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertificationRequest;
use App\Models\Certification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificationController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/certifications/index', [
            'certifications' => $this->paginateTable(
                Certification::query()->orderByDesc('issued_at'),
                $request,
                ['title', 'issuer'],
                ['title', 'issuer', 'issued_at'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'issuer', 'issued_at']),
        ]);
    }

    public function store(CertificationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Certification::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil ditambahkan.']);

        return to_route('admin.certifications.index');
    }

    public function update(CertificationRequest $request, Certification $certification): RedirectResponse
    {
        $data = $request->validated();

        $certification->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil diperbarui.']);

        return to_route('admin.certifications.index');
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $certification->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sertifikasi berhasil dihapus.']);

        return to_route('admin.certifications.index');
    }
}
