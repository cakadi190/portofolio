<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityPlatform;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DevCommunityRequest;
use App\Models\DevCommunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DevCommunityController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/dev-communities/index', [
            'communities' => Inertia::scroll($this->paginateTable(
                DevCommunity::query()->orderByDesc('is_featured')->orderBy('name'),
                $request,
                ['name', 'description'],
                ['name', 'platform', 'is_featured'],
            )),
            'filters' => $this->tableFilters($request, ['name', 'platform', 'is_featured']),
            'platforms' => CommunityPlatform::options(),
        ]);
    }

    public function store(DevCommunityRequest $request): RedirectResponse
    {
        DevCommunity::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.komunitas_berhasil_ditambahkan')]);

        return to_route('admin.dev-communities.index');
    }

    public function update(DevCommunityRequest $request, DevCommunity $devCommunity): RedirectResponse
    {
        $devCommunity->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.komunitas_berhasil_diperbarui')]);

        return to_route('admin.dev-communities.index');
    }

    public function destroy(DevCommunity $devCommunity): RedirectResponse
    {
        $devCommunity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('flash.komunitas_berhasil_dihapus')]);

        return to_route('admin.dev-communities.index');
    }
}
