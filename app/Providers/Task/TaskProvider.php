<?php

namespace App\Providers\Task;

use App\Contracts\Task\TaskInterface;
use App\Repository\Task\TaskRepository;
use Illuminate\Support\ServiceProvider;

class TaskProvider extends ServiceProvider
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
        $this->app->bind(TaskInterface::class, TaskRepository::class);
    }
}
