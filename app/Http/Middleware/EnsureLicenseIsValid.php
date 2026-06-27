<?php

namespace App\Http\Middleware;

use App\Services\LicenseGuardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    public function __construct(private readonly LicenseGuardService $licenses)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request) || $this->licenses->allowsRuntime()) {
            return $next($request);
        }

        $status = $this->licenses->status();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Aptoria license guard blocked this runtime.',
                'state' => $status['state'] ?? 'invalid',
                'license_url' => route('license.activate'),
            ], 402);
        }

        return redirect()->route('license.activate');
    }

    private function shouldSkip(Request $request): bool
    {
        if (strtolower((string) config('aptoria.domain.role', 'local')) === 'landing') {
            return true;
        }

        return $request->is('license/invalid')
            || $request->is('license/activate')
            || $request->is('license/activate/*')
            || $request->is('setup')
            || $request->is('setup/*')
            || $request->is('license/request.json')
            || $request->is('license/status.json')
            || $request->is('language/*')
            || $request->is('up')
            || $request->is('assets/*')
            || $request->is('favicon.ico');
    }
}
