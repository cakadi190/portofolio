<?php

namespace App\Http\Controllers;

use App\Services\IconService;
use Illuminate\Http\Response;

class IconController extends Controller
{
    /**
     * Serve a locally cached icon as an immutable, browser-cacheable SVG.
     */
    public function show(IconService $icons, string $prefix, string $name): Response
    {
        $svg = $icons->svg($prefix, $name);

        abort_if($svg === null, 404);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
