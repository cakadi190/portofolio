<?php

namespace App\Http\Controllers;

use App\Services\CertificateTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfTrustController extends Controller
{
    /**
     * Report which trust store (Komdigi or the server's CA bundle) vouches for a chain's top certificate.
     */
    public function __invoke(Request $request, CertificateTrustService $trust): JsonResponse
    {
        $validated = $request->validate(['certificate' => ['required', 'string', 'max:20000']]);
        $der = base64_decode($validated['certificate'], true);

        abort_if($der === false || $der === '', 422, 'Sertifikat tidak valid.');

        return response()->json($trust->anchorFor($der) ?? ['source' => null, 'anchor' => null]);
    }
}
