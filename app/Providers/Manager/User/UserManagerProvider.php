<?php

namespace App\Providers\Manager\User;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Manager\User\UserInterface;
use App\Repository\Manager\User\UserRepository;

class UserManagerProvider extends ServiceProvider
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
        $this->app->bind(UserInterface::class, UserRepository::class);
    }
}
