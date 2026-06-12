<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('whatsapp.internal_api_key', '');
        $provided = $request->header('X-Internal-Key', '');

        if (empty($expected) || !hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
