<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Show the services page.
     */
    public function index(): Response
    {
        return Inertia::render('service/index');
    }
}
