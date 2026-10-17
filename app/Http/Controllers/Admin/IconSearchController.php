<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconSearchController extends Controller
{
    /**
     * Search icons for the portfolio technology picker and the rich editor.
     */
    public function __invoke(Request $request, IconService $icons): JsonResponse
    {
        $query = $request->validate(['query' => ['nullable', 'string', 'max:60']])['query'] ?? '';

        return response()->json(['data' => $icons->search($query)]);
    }
}
