<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioGalleryRequest;
use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioGalleryController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/portfolio-galleries/index', [
            'portfolioGalleries' => PortfolioGallery::query()
                ->with('portfolio:id,name')
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/portfolio-galleries/create', [
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PortfolioGalleryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image_url'] = $this->storeUpload($request->file('image'), 'portfolio-galleries');

        PortfolioGallery::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolio-galleries.index');
    }

    public function edit(PortfolioGallery $portfolioGallery): Response
    {
        return Inertia::render('admin/portfolio-galleries/edit', [
            'portfolioGallery' => $portfolioGallery,
            'portfolios' => Portfolio::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(PortfolioGalleryRequest $request, PortfolioGallery $portfolioGallery): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->deleteUpload($portfolioGallery->image_url);
            $data['image_url'] = $this->storeUpload($request->file('image'), 'portfolio-galleries');
        }

        $portfolioGallery->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil diperbarui.']);

        return to_route('admin.portfolio-galleries.index');
    }

    public function destroy(PortfolioGallery $portfolioGallery): RedirectResponse
    {
        $this->deleteUpload($portfolioGallery->image_url);
        $portfolioGallery->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri portofolio berhasil dihapus.']);

        return to_route('admin.portfolio-galleries.index');
    }
}
