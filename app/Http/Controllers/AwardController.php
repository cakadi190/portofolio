<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Inertia\Inertia;
use Inertia\Response;

class AwardController extends Controller
{
    /**
     * Show the list of awards and certifications.
     */
    public function index(): Response
    {
        return Inertia::render('award/index', [
            'awards' => Award::query()
                ->orderByDesc('awarded_at')
                ->orderByDesc('year')
                ->get()
                ->map(fn (Award $award): array => [
                    'eventName' => $award->event_name,
                    'title' => $award->title,
                    'icon' => $award->icon,
                    'year' => $award->year,
                    'rank' => $award->rank,
                    'awardedAt' => $award->awarded_at,
                ]),
        ]);
    }
}
