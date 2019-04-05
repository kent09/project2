<?php

namespace App\Providers\Manager;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Manager\SocialInterface;
use App\Repository\Manager\SocialRepository;

class SocialProvider extends ServiceProvider
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
        $this->app->bind(SocialInterface::class, SocialRepository::class);
    }
}
