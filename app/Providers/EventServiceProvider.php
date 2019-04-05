<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ExampleEvent' => [
            'App\Listeners\ExampleListener',
        ],
        'App\Events\NewTaskTransaction' => [
            'App\Listeners\LogTaskTransaction',
        ],
        'App\Events\NewGiftCoin' => [
            'App\Listeners\LogGiftCoinTransaction',
        ],
        'App\Events\NewCoinGiftNotification' => [
            'App\Listeners\SaveCoinGiftNotification',
        ],
        'App\Events\NewPrivateChat' => [
            'App\Listeners\SavePrivateChat'
        ],
        'App\Events\NewGroupChat' => [
            'App\Listeners\SaveGroupChat'
        ],
        'App\Events\NewFollowNotification' => [
            'App\Listeners\NotifyUserWhenFollowed',
        ],
        'App\Events\NewUnfollowNotification' => [
            'App\Listeners\NotifyUserWhenUnfollowed',
        ],
    ];
}
