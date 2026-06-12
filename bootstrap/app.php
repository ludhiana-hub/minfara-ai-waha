<?php

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
        // CORS — baca dari config/cors.php
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->validateCsrfTokens(except: [
            'api/*',  // semua API routes tidak butuh CSRF (pakai XSRF-TOKEN header)
        ]);
        $middleware->alias([
            'localhost'    => \App\Http\Middleware\LocalhostOnly::class,
            'auth.internal' => \App\Http\Middleware\InternalApiAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
