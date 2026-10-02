<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TechnologyRequest;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/technologies/index', [
            'technologies' => $this->paginateTable(
                Technology::query()->orderBy('name'),
                $request,
                ['name'],
                ['name'],
            ),
            'filters' => $this->tableFilters($request, ['name']),
        ]);
    }

    public function store(TechnologyRequest $request): RedirectResponse
    {
        Technology::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Teknologi berhasil ditambahkan.']);

        return to_route('admin.technologies.index');
    }

    public function update(TechnologyRequest $request, Technology $technology): RedirectResponse
    {
        $technology->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Teknologi berhasil diperbarui.']);

        return to_route('admin.technologies.index');
    }

    public function destroy(Technology $technology): RedirectResponse
    {
        $technology->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Teknologi berhasil dihapus.']);

        return to_route('admin.technologies.index');
    }
}
