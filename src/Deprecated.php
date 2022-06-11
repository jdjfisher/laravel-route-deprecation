<?php

namespace Jdjfisher\LaravelRouteDeprecation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;

class Deprecated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        if ($route instanceof Route) {
            Log::warning("Deprecation Notice: {$request->method()} $route->uri");
        }
    
        return $next($request);
    }
}
