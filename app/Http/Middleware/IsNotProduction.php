<?php

namespace App\Http\Middleware;

use Closure;

class IsNotProduction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (config('app.url') === 'https://voucher-admin.alexandrarose.org.uk') {
            // We should only see thes routes in dev and staging environs.
            return response('Unauthorized', 418);
        }
        return $next($request);
    }
}
