<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcademicScoreType;
use App\Enums\EducationLevel;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/educations/index', [
            'educations' => $this->paginateTable(
                Education::query()->orderByDesc('start_date'),
                $request,
                ['name', 'place'],
                ['name', 'level', 'place', 'start_date'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'level', 'place', 'start_date']),
            'levels' => EducationLevel::options(),
            'scoreTypes' => AcademicScoreType::options(),
        ]);
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Education::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil ditambahkan.']);

        return to_route('admin.educations.index');
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        $data = $request->validated();

        $education->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil diperbarui.']);

        return to_route('admin.educations.index');
    }

    public function destroy(Education $education): RedirectResponse
    {
        $education->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Riwayat pendidikan berhasil dihapus.']);

        return to_route('admin.educations.index');
    }
}
