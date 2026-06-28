<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Aptoria customer builds do not expose license issuer/admin APIs.
// The runtime lease endpoint is intentionally present so the license role can
// fail with a controlled API response instead of an HTML 404/session error.
Route::match(['GET', 'POST'], '/license/runtime-lease', function (Request $request) {
    $role = strtolower(trim((string) config('aptoria.domain.role', 'local')));
    $host = strtolower($request->getHost());

    if ($role !== 'license' && $host !== 'license.aptoria.dev') {
        return response()->json([
            'ok' => false,
            'code' => 'license_host_required',
            'message' => 'License runtime lease requests must use the license authority host.',
        ], 404);
    }

    return response()->json([
        'ok' => false,
        'code' => 'license_authority_not_configured',
        'message' => 'This package does not expose a public license authority issuer endpoint.',
    ], 501);
})->name('api.license.runtime-lease');
