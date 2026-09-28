<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PortfolioRequest;
use App\Models\Career;
use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    use HandlesUploads;

    public function index(): Response
    {
        return Inertia::render('admin/portfolios/index', [
            'portfolios' => Portfolio::query()
                ->with(['technologies:id,name', 'categories:id,name', 'careers:id'])
                ->withCount(['technologies', 'categories', 'galleries', 'ratings'])
                ->orderBy('name')
                ->paginate(20),
            ...$this->options(),
        ]);
    }

    public function store(PortfolioRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'technologies', 'categories', 'careers']);
        $data['image'] = $this->storeImage('image', 'portfolios');

        $portfolio = Portfolio::query()->create($data);
        $this->syncRelations($portfolio, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil ditambahkan.']);

        return to_route('admin.portfolios.index');
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'technologies', 'categories', 'careers']);

        if ($request->hasFile('image')) {
            $this->deleteUpload($portfolio->image);
            $data['image'] = $this->storeImage('image', 'portfolios');
        }

        $portfolio->update($data);
        $this->syncRelations($portfolio, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil diperbarui.']);

        return to_route('admin.portfolios.index');
    }

    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        $this->deleteUpload($portfolio->image);
        $portfolio->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Portofolio berhasil dihapus.']);

        return to_route('admin.portfolios.index');
    }

    private function syncRelations(Portfolio $portfolio, PortfolioRequest $request): void
    {
        $portfolio->technologies()->sync($request->validated('technologies', []));
        $portfolio->categories()->sync($request->validated('categories', []));
        $portfolio->careers()->sync($request->validated('careers', []));
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
