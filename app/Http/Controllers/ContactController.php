<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show the contact page.
     */
    public function index(): Response
    {
        return Inertia::render('contact/index');
    }
}
