<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Support\AppLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * Saves the interface language for pages that have no language in their URL.
 */
class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');

        $request->user()?->update(['locale' => $locale]);

        return back()->withCookie(Cookie::make(AppLocale::COOKIE, $locale, 60 * 24 * 365));
    }
}
