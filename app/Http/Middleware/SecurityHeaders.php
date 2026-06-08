<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure()) {
            $hsts = 'max-age='.(int) config('aptoria.security_headers.hsts_max_age', 31536000);
            if ((bool) config('aptoria.security_headers.hsts_include_subdomains', true)) {
                $hsts .= '; includeSubDomains';
            }
            if ((bool) config('aptoria.security_headers.hsts_preload', false)) {
                $hsts .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hsts);
            $response->headers->set('Content-Security-Policy', 'upgrade-insecure-requests');
        }

        if ((bool) config('aptoria.security_headers.content_security_policy_report_only', true)) {
            $response->headers->set(
                'Content-Security-Policy-Report-Only',
                "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'"
            );
        }

        if ((bool) config('aptoria.security_headers.enable_cache_control_for_sensitive_pages', true)
            && $this->isSensitivePage($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function isSensitivePage(Request $request): bool
    {
        return $request->is('login')
            || $request->is('setup')
            || $request->is('setup/*')
            || $request->is('settings')
            || $request->is('settings/*');
    }
}
