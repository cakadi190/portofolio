<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AwardType;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AwardRequest;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AwardController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/awards/index', [
            'awards' => $this->paginateTable(
                Award::query()->orderByDesc('awarded_at')->orderByDesc('year'),
                $request,
                ['title', 'event_name', 'year', 'rank'],
                ['title', 'event_name', 'type', 'year', 'rank'],
            ),
            'filters' => $this->tableFilters($request, ['title', 'event_name', 'type', 'year', 'rank']),
            'types' => AwardType::options(),
        ]);
    }

    public function store(AwardRequest $request): RedirectResponse
    {
        Award::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil ditambahkan.']);

        return to_route('admin.awards.index');
    }

    public function update(AwardRequest $request, Award $award): RedirectResponse
    {
        $award->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil diperbarui.']);

        return to_route('admin.awards.index');
    }

    public function destroy(Award $award): RedirectResponse
    {
        $award->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penghargaan berhasil dihapus.']);

        return to_route('admin.awards.index');
    }
}
