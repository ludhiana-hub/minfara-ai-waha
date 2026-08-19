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
        // Deploy production ada di belakang reverse proxy (Traefik/Dokploy) yang terminate
        // SSL lalu forward ke container sebagai plain HTTP — tanpa ini, Laravel tidak tahu
        // koneksi asli klien itu HTTPS, sehingga route()/url() generate skema http:// (bikin
        // Chrome block submit form dengan "not secure"). '*' dipakai karena IP proxy internal
        // Dokploy tidak statis/tidak kita kontrol; header X-Forwarded-* yang dipercaya standar
        // (proto, host, port, for) — aman karena container tidak exposed langsung ke internet.
        $middleware->trustProxies(at: '*');

        // CORS — baca dari config/cors.php
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->validateCsrfTokens(except: [
            'api/*',  // semua API routes tidak butuh CSRF (pakai XSRF-TOKEN header)
        ]);
        $middleware->alias([
            'localhost'    => \App\Http\Middleware\CmsIpMiddleware::class,
            'auth.internal' => \App\Http\Middleware\InternalApiAuth::class,
            'super_admin'  => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // Named 'cms.login'/'cms.dashboard' (not the framework defaults 'login'/'dashboard')
        // since this app has no other auth surface — the whole site is the CMS.
        $middleware->redirectGuestsTo(fn () => route('cms.login'));
        $middleware->redirectUsersTo(fn () => route('cms.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
