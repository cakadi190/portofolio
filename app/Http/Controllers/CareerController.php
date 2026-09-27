<?php

namespace App\Http\Controllers;

use App\Models\Career;
use Inertia\Inertia;
use Inertia\Response;

class CareerController extends Controller
{
    /**
     * Show the career history.
     */
    public function index(): Response
    {
        return Inertia::render('career/index', [
            'careers' => Career::query()
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (Career $career): array => [
                    'position' => $career->position,
                    'company' => $career->company,
                    'location' => $career->location,
                    'startDate' => $career->start_date,
                    'endDate' => $career->end_date,
                ]),
        ]);
    }
}
