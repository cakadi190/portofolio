<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/organizations/index', [
            'organizations' => Organization::query()->orderByDesc('start_date')->paginate(20),
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
