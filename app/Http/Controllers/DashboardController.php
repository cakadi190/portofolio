<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard) {}

    /**
     * The admin dashboard is for blogging staff and admins; other accounts
     * (plain commenters) are sent back to the public site.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->canManageBlog()) {
            return to_route('home');
        }

        return Inertia::render('dashboard/index', [
            'summary' => $this->dashboard->summary($request->user()),
        ]);
    }
}
