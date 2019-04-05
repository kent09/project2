<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Roles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, ... $roles)
    {
        // Do stuff before
        if (!Auth::user() || Auth::user()->role() === null) {
            return \res('Unauthorized', null, 401);
        }
        if (!in_array(Auth::user()->role()->slug, $roles)) {
            return \res('Invalid Role', null, 403);
        }

        $response = $next($request);

        // Do stuff after

        return $response;
    }
}
