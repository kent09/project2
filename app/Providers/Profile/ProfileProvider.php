<?php

namespace App\Providers\Profile;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Profile\ProfileInterface;
use App\Repository\Profile\ProfileRepository;

class ProfileProvider extends ServiceProvider
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
        $this->app->bind(ProfileInterface::class, ProfileRepository::class);
    }
}
