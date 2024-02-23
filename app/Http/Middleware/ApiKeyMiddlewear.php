<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddlewear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response

    {
        $key = $request->header('API-Key');
        if (!$key || $key !== config('app.api_key')) {
            throw new AuthenticationException('Wrong api key');
        }
        return $next($request);
    }
}
