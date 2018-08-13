<?php

namespace App\Http\Middleware;

use Closure;

class XssHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-XSS-Protection', '1; mode=block', false);

        return $response;

    }
}
