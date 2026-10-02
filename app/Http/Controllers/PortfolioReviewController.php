<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioReviewRequest;
use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PortfolioReviewController extends Controller
{
    /**
     * Store a visitor review; it stays hidden until approved by an admin.
     */
    public function store(PortfolioReviewRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio->ratings()->create($request->validated() + ['is_approved' => false]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Terima kasih! Ulasan Anda akan tampil setelah ditinjau.',
        ]);

        return to_route('portfolios.show', $portfolio);
    }
}
