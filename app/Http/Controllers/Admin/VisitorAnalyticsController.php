<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Visitors\VisitorStats;
use Inertia\Inertia;
use Inertia\Response;

class VisitorAnalyticsController extends Controller
{
    public function index(VisitorStats $stats): Response
    {
        return Inertia::render('admin/visitor-analytics/index', ['stats' => $stats->summary()]);
    }
}
