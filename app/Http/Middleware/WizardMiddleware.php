<?php

namespace App\Http\Middleware;

use App\Traits\UtilityTrait;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class WizardMiddleware
{
    use UtilityTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $response = $next($request);
        $data = ['wizard' => true];

        // Post-Middleware Action
        $user = User::where('id', Auth::id())->needToWizard()->first();

        if( $user )
            return static::response('This is user needs to be in wizard view', $data, 200, 'success');
        return $response;
    }
}
