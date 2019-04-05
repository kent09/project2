<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class SettingsDefault extends Command
{
    protected $cron;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:default';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default entry for every settings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->cron = new CronRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->cron->settingsDefaults($this);
    }
}
