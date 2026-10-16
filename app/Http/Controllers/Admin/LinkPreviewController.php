<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkPreviewController extends Controller
{
    /**
     * Metadata for the editor's link preview block.
     */
    public function __invoke(Request $request, LinkPreviewService $previews): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'url:http,https', 'max:2048']]);

        $preview = $previews->preview($data['url']);

        return $preview === null
            ? response()->json(['message' => __('flash.pratinjau_tautan_tidak_dapat_diambil')], 422)
            : response()->json($preview);
    }
}
