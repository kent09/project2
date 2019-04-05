<?php

namespace App\Providers\Wizard;

use App\Contracts\Wizard\WizardInterface;
use App\Repository\Wizard\WizardRepository;
use Illuminate\Support\ServiceProvider;

class WizardProvider extends ServiceProvider
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
        $this->app->bind(WizardInterface::class, WizardRepository::class);
    }
}
