<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Empty304
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $code = $response->getStatusCode();
        if ($code>=300 && $code<=399) {
            $response->setContent("");
        }

        return $response;
    }
}
