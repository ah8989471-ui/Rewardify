<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Vercel's proxy the app must trust X-Forwarded-* headers
        // or generated URLs and secure cookies use http:// on an
        // https:// site. Harmless on localhost too.
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['postback/*']);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active.account' => \App\Http\Middleware\EnsureActiveAccount::class,
            'verified.email' => \App\Http\Middleware\EnsureEmailVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
