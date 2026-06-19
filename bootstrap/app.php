<?php

use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureApplicationIsInstalled;
use App\Http\Middleware\EnsurePasswordChangeIsCompleted;
use App\Http\Middleware\EnsureProjectAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
            EnsureApplicationIsInstalled::class,
            EnforceSessionTimeout::class,
            EnsureProjectAccess::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'password.changed' => EnsurePasswordChangeIsCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // v0.0.2 keeps the exception pipeline clean and framework-default.
    })->create();
