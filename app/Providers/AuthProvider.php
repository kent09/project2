<?php

namespace App\Providers;

use App\Contracts\AuthInterface;
use App\Repository\AuthRepository;
use Illuminate\Support\ServiceProvider;

class AuthProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind( AuthInterface::class, AuthRepository::class);
    }
}
