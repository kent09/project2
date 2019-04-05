<?php

namespace App\Providers\Bank;

use App\Contracts\Bank\BankInterface;
use App\Repository\Bank\BankRepository;
use Illuminate\Support\ServiceProvider;

class BankProvider extends ServiceProvider
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
        //
        $this->app->bind(BankInterface::class, BankRepository::class);
    }
}
