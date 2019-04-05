<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class Bonus extends Command
{
    private $cron;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Bonus:task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Bonus';

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
        $this->cron->bonus();
    }
}
