<?php

namespace App\Providers\Notification;

use Illuminate\Support\ServiceProvider;
use App\Contracts\NotificationInterface;
use App\Repository\NotificationRepository;


class NotificationProvider extends ServiceProvider
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
        $this->app->bind(NotificationInterface::class, NotificationRepository::class);
    }
}
