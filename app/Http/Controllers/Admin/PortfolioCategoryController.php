<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioCategoryRequest;
use App\Models\PortfolioCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioCategoryController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/portfolio-categories/index', [
            'portfolioCategories' => $this->paginateTable(
                PortfolioCategory::query()->withCount('portfolios')->orderBy('name'),
                $request,
                ['name', 'color'],
                ['name', 'color', 'portfolios_count'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'color', 'portfolios_count']),
        ]);
    }

    public function store(PortfolioCategoryRequest $request): RedirectResponse
    {
        PortfolioCategory::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolio-categories.index');
    }

    public function update(PortfolioCategoryRequest $request, PortfolioCategory $portfolioCategory): RedirectResponse
    {
        $portfolioCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori portofolio berhasil diperbarui.']);

        return to_route('admin.portfolio-categories.index');
    }

    public function destroy(PortfolioCategory $portfolioCategory): RedirectResponse
    {
        $portfolioCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori portofolio berhasil dihapus.']);

        return to_route('admin.portfolio-categories.index');
    }
}
