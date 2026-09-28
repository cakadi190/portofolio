<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CareerRequest;
use App\Models\Career;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CareerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/careers/index', [
            'careers' => Career::query()->orderByDesc('start_date')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/careers/create', [
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(CareerRequest $request): RedirectResponse
    {
        $career = Career::query()->create($request->safe()->except('portfolios'));
        $career->portfolios()->sync($request->validated('portfolios', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karier berhasil ditambahkan.']);

        return to_route('admin.careers.index');
    }

    public function edit(Career $career): Response
    {
        return Inertia::render('admin/careers/edit', [
            'career' => $career->load('portfolios:id'),
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(CareerRequest $request, Career $career): RedirectResponse
    {
        $career->update($request->safe()->except('portfolios'));
        $career->portfolios()->sync($request->validated('portfolios', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karier berhasil diperbarui.']);

        return to_route('admin.careers.index');
    }

    public function destroy(Career $career): RedirectResponse
    {
        $career->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karier berhasil dihapus.']);

        return to_route('admin.careers.index');
    }
}
