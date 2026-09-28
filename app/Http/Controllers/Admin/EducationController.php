<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicScoreType;
use App\Enums\EducationLevel;
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
            'levels' => EducationLevel::options(),
            'scoreTypes' => AcademicScoreType::options(),
        ]);
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeImage('logo', 'educations', 512, 512, 100);
        }

        Education::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil ditambahkan.']);

        return to_route('admin.educations.index');
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $this->deleteUpload($education->logo);
            $data['logo'] = $this->storeImage('logo', 'educations', 512, 512, 100);
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
