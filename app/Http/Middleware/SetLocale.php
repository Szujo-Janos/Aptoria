<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('aptoria.supported_locales', ['en' => 'English']));
        $defaultLocale = app(SettingService::class)->get('app.default_locale', config('aptoria.default_locale', config('app.locale', 'en')));
        $userLocale = $request->user()?->locale;
        $sessionLocale = $request->session()->get('locale', $userLocale ?: $defaultLocale);
        $locale = in_array($sessionLocale, $supportedLocales, true) ? $sessionLocale : $defaultLocale;

        App::setLocale($locale);

        return $next($request);
    }
}
