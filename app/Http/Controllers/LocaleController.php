<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Support\AppLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * Saves the interface language for pages that have no language in their URL, then
 * returns to the page the visitor was on. The page sends its own path as `redirect`
 * (same-site paths only) so the return trip does not depend on the `Referer` header.
 */
class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        $request->user()?->update(['locale' => $locale]);

        $redirect = $request->validated('redirect');

        return ($redirect === null ? back() : redirect($redirect))->withCookie(Cookie::make(AppLocale::COOKIE, $locale, 60 * 24 * 365));
    }
}
