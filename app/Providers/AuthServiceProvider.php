<?php

namespace App\Providers;

use App\Traits\AuthenticationTrait;
use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    use AuthenticationTrait;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {

            $token = $this->resolveAuthenticatedUser($request->header('authorization'));

            if (is_array($token)) {
                if(count($token) > 0)
                    return User::where(function($query) use (& $token) {
                        $query->where('id', $token['user_id'])->orWhere('email', $token['email']);
                    })->first();
            }
        });
    }
}
