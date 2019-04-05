<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class CheckSecurityItems extends Command
{
    protected $cron;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:security';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Security Item and Ban all affected';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CronRepository $cron)
    {
        parent::__construct();
        $this->cron = $cron;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->cron->checkSecurityItems();
    }
}
