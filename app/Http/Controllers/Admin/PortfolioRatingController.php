<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioRatingRequest;
use App\Models\Portfolio;
use App\Models\PortfolioRating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioRatingController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/portfolio-ratings/index', [
            'portfolioRatings' => $this->paginateTable(
                PortfolioRating::query()
                    ->with('portfolio:id,name')
                    ->orderByDesc('created_at'),
                $request,
                ['comment', 'portfolio.name'],
                ['rating', 'created_at'],
            ),
            'filters' => $this->tableFilters($request, ['rating', 'created_at']),
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PortfolioRatingRequest $request): RedirectResponse
    {
        PortfolioRating::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ulasan portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolio-ratings.index');
    }

    public function update(PortfolioRatingRequest $request, PortfolioRating $portfolioRating): RedirectResponse
    {
        $portfolioRating->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ulasan portofolio berhasil diperbarui.']);

        return to_route('admin.portfolio-ratings.index');
    }

    public function destroy(PortfolioRating $portfolioRating): RedirectResponse
    {
        $portfolioRating->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ulasan portofolio berhasil dihapus.']);

        return to_route('admin.portfolio-ratings.index');
    }
}
