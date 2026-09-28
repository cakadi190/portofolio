<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/educations/index', [
            'educations' => Education::query()->orderByDesc('start_date')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/educations/create');
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeUpload($request->file('logo'), 'educations');
        }

        Education::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil ditambahkan.']);

        return to_route('admin.educations.index');
    }

    public function edit(Education $education): Response
    {
        return Inertia::render('admin/educations/edit', [
            'education' => $education,
        ]);
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $this->deleteUpload($education->logo);
            $data['logo'] = $this->storeUpload($request->file('logo'), 'educations');
        }

        $education->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil diperbarui.']);

        return to_route('admin.educations.index');
    }

    public function destroy(Education $education): RedirectResponse
    {
        $this->deleteUpload($education->logo);
        $education->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil dihapus.']);

        return to_route('admin.educations.index');
    }
}
