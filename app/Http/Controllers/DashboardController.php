<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The admin dashboard is for blogging staff and admins; other accounts
     * (plain commenters) are sent back to the public site.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->canManageBlog()) {
            return to_route('home');
        }

        return Inertia::render('dashboard/index');
    }
}
