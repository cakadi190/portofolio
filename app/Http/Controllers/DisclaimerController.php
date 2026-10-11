<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DisclaimerController extends Controller
{
    /**
     * Show the disclaimer, terms of use, and comment policy page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('disclaimer/index');
    }
}
