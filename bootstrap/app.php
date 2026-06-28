<?php

use App\Http\Middleware\BlockDangerousDemoActions;
use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\EnforceDomainRole;
use App\Http\Middleware\EnforceDemoViewerReadOnly;
use App\Http\Middleware\EnforceDemoShowcaseGuard;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureApplicationIsInstalled;
use App\Http\Middleware\EnsurePasswordChangeIsCompleted;
use App\Http\Middleware\EnsureLicenseIsValid;
use App\Http\Middleware\EnsureProjectAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\DomainRoleRedirector;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            EnforceDomainRole::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
            EnsureApplicationIsInstalled::class,
            EnsureLicenseIsValid::class,
            EnforceSessionTimeout::class,
            BlockDangerousDemoActions::class,
            EnforceDemoShowcaseGuard::class,
            EnforceDemoViewerReadOnly::class,
            EnsureProjectAccess::class,
        ]);


        $middleware->api(prepend: [
            EnforceDomainRole::class,
        ]);

        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            return DomainRoleRedirector::unauthenticatedRedirectTo($request);
        });

        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'password.changed' => EnsurePasswordChangeIsCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // v0.0.2 keeps the exception pipeline clean and framework-default.
    })->create();
