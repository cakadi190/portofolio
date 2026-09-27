<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    /**
     * Show the "about me" page.
     */
    public function me(): Response
    {
        return Inertia::render('tentang/saya', [
            'yearsExperience' => Carbon::parse('2015-03-17')->diffInYears(now()),
            'yearsServing' => Carbon::parse('2019-05-20')->diffInYears(now()),
            'totalProjects' => Portfolio::query()->count(),
        ]);
    }

    /**
     * Show the "about this site" page.
     */
    public function site(): Response
    {
        return Inertia::render('tentang/situs');
    }

    /**
     * Show the "skills" page.
     */
    public function skills(): Response
    {
        return Inertia::render('tentang/skill');
    }
}
