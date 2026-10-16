<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TranslateRequest;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TranslationController extends Controller
{
    /**
     * A machine-translated draft of one field, for the editor to load and the writer to review.
     */
    public function __invoke(TranslateRequest $request, TranslationService $translations): JsonResponse
    {
        try {
            $translated = $translations->translate(
                $request->validated('text'),
                $request->validated('from'),
                $request->validated('to'),
                $request->validated('format'),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json(['text' => $translated]);
    }
}
