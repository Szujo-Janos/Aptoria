<?php

namespace App\Http\Middleware;

use App\Models\ProgramSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('aptoria.supported_locales', ['en', 'hu']);
        $programDefaultLocale = ProgramSetting::get('app.default_locale', config('aptoria.default_locale', config('app.locale')));
        $locale = $request->user()?->locale ?: $request->session()->get('locale', $programDefaultLocale);

        if (! in_array($locale, $supported, true)) {
            $locale = $programDefaultLocale;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
