<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = array_keys(config('aptoria.supported_locales', ['en' => 'English']));

        abort_unless(in_array($locale, $supportedLocales, true), 404);

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back()->with('success', __('messages.language.changed'));
    }
}
