<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Permissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, ... $actions)
    {
        if (!Auth::user() || Auth::user()->role() === null) {
            return \res('Unauthorized', null, 401);
        }
        if (!Auth::user()->hasPermission($actions)) {
            return \res('Forbidden', null, 403);
        }

        $response = $next($request);

        // Do stuff after
        // Tracker Function Could be added Here

        return $response;
    }
}
