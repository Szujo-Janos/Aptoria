<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\EnsureApplicationIsInstalled;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureWorkspaceAccess;
use App\Http\Middleware\EnsureSetupAccessIsAuthorized;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnforceSessionTimeout;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class, EnsureApplicationIsInstalled::class, EnforceSessionTimeout::class]);
        $middleware->alias([
            'admin' => EnsureWorkspaceAccess::class,
            'system.admin' => EnsureAdminUser::class,
            'setup.access' => EnsureSetupAccessIsAuthorized::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Central exception customization will be added in later versions.
    })
    ->create();
