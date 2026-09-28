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
            'careers' => Career::query()->with('portfolios:id')->orderByDesc('start_date')->paginate(20),
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
