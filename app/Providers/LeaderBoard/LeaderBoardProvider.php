<?php

namespace App\Providers\LeaderBoard;

use Illuminate\Support\ServiceProvider;
use App\Contracts\LeaderBoardInterface;
use App\Repository\LeaderBoardRepository;

class LeaderBoardProvider extends ServiceProvider
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
        $this->app->bind(LeaderBoardInterface::class, LeaderBoardRepository::class);
    }
}
