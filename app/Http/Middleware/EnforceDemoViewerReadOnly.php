<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDemoViewerReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $this->isDemoViewer((string) $user->email)) {
            return $next($request);
        }

        if ($this->isSafeRead($request) || $request->routeIs('logout')) {
            return $next($request);
        }

        abort(403, __('messages.demo_mode.viewer_read_only'));
    }

    private function isEnabled(): bool
    {
        return (bool) config('aptoria.demo.mode', false)
            && (bool) config('aptoria.demo.viewer_read_only', true)
            && (string) config('aptoria.demo.viewer_mode', 'readonly') === 'readonly';
    }

    private function isDemoViewer(string $email): bool
    {
        $configured = strtolower(trim((string) config('aptoria.demo.demo_user_email', 'demo@aptoria.dev')));

        return $configured !== '' && strtolower(trim($email)) === $configured;
    }

    private function isSafeRead(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
