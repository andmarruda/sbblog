<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language. Saved on the user when signed in, and in a cookie
     * so it also applies before login.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
        ]);

        $request->user()?->update([
            'language_id' => Language::query()->where('code', $validated['locale'])->value('id'),
        ]);

        return back()->withCookie(cookie()->forever(SetLocale::COOKIE, $validated['locale']));
    }
}
