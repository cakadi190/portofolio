<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioGalleryRequest;
use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioGalleryController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/portfolio-galleries/index', [
            'portfolioGalleries' => $this->paginateTable(
                PortfolioGallery::query()
                    ->with('portfolio:id,name')
                    ->orderByDesc('created_at'),
                $request,
                ['description', 'portfolio.name'],
                ['created_at'],
            ),
            'filters' => $this->tableFilters($request, ['created_at']),
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PortfolioGalleryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        PortfolioGallery::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolio-galleries.index');
    }

    public function update(PortfolioGalleryRequest $request, PortfolioGallery $portfolioGallery): RedirectResponse
    {
        $data = $request->validated();

        $portfolioGallery->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil diperbarui.']);

        return to_route('admin.portfolio-galleries.index');
    }

    public function destroy(PortfolioGallery $portfolioGallery): RedirectResponse
    {
        $portfolioGallery->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil dihapus.']);

        return to_route('admin.portfolio-galleries.index');
    }
}
