<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/organizations/index', [
            'organizations' => $this->paginateTable(
                Organization::query()->orderByDesc('start_date'),
                $request,
                ['name'],
                ['name', 'start_date'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'start_date']),
        ]);
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        Organization::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisasi berhasil ditambahkan.']);

        return to_route('admin.organizations.index');
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisasi berhasil diperbarui.']);

        return to_route('admin.organizations.index');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisasi berhasil dihapus.']);

        return to_route('admin.organizations.index');
    }
}
