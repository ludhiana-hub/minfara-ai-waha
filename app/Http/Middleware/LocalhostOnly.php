<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalhostOnly
{
    private const ALLOWED_IPS = ['127.0.0.1', '::1'];

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->server('REMOTE_ADDR');

        if (!in_array($ip, self::ALLOWED_IPS, strict: true)) {
            abort(403, 'Akses hanya dari localhost.');
        }

        return $next($request);
    }
}
