<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsIpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = env('CMS_ALLOWED_IPS', '127.0.0.1,::1');

        // Wildcard '*' = izinkan semua IP
        if (trim($allowed) === '*') {
            return $next($request);
        }

        $allowedList = array_map('trim', explode(',', $allowed));
        $ip = $request->server('REMOTE_ADDR');

        if (!in_array($ip, $allowedList, strict: true)) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return $next($request);
    }
}
