<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioRequest;
use App\Models\Career;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/portfolios/index', [
            'portfolios' => $this->paginateTable(
                Portfolio::query()
                    ->with(['technologies:id,name', 'categories:id,name', 'careers:id'])
                    ->withCount(['technologies', 'categories', 'galleries', 'ratings'])
                    ->orderBy('name'),
                $request,
                ['name'],
                ['name', 'is_private'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'is_private']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/portfolios/form', [
            'portfolio' => null,
            ...$this->options(),
        ]);
    }

    public function edit(Portfolio $portfolio): Response
    {
        return Inertia::render('admin/portfolios/form', [
            'portfolio' => $portfolio->load(['technologies:id', 'categories:id', 'careers:id', 'galleries:id,portfolio_id,image_url,description']),
            ...$this->options(),
        ]);
    }

    public function store(PortfolioRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['technologies', 'categories', 'careers', 'galleries']);

        $portfolio = Portfolio::query()->create($data);
        $this->syncRelations($portfolio, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolios.edit', $portfolio);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $data = $request->safe()->except(['technologies', 'categories', 'careers', 'galleries']);

        $portfolio->update($data);
        $this->syncRelations($portfolio, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil diperbarui.']);

        return to_route('admin.portfolios.edit', $portfolio);
    }

    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        $portfolio->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil dihapus.']);

        return to_route('admin.portfolios.index');
    }

    private function syncRelations(Portfolio $portfolio, PortfolioRequest $request): void
    {
        $portfolio->technologies()->sync($request->validated('technologies', []));
        $portfolio->categories()->sync($request->validated('categories', []));
        $portfolio->careers()->sync($request->validated('careers', []));

        $portfolio->galleries()->delete();
        $portfolio->galleries()->createMany(array_values($request->validated('galleries', [])));
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'technologies' => Technology::query()->orderBy('name')->get(['id', 'name']),
            'categories' => PortfolioCategory::query()->orderBy('name')->get(['id', 'name']),
            'careers' => Career::query()->orderBy('position')->get(['id', 'position', 'company']),
        ];
    }
}
