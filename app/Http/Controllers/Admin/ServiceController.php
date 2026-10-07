<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/services/index', [
            'services' => $this->paginateTable(
                Service::query()->withCount('portfolios')->orderBy('name'),
                $request,
                ['name'],
                ['name'],
            ),
            'filters' => $this->tableFilters($request, ['name']),
        ]);
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/form', [
            'service' => $service,
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan berhasil diperbarui.']);

        return to_route('admin.services.edit', $service);
    }
}
