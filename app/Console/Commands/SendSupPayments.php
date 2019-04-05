<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class SendSupPayments extends Command
{
    private $cron;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:suppayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Sup Payments';

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
        $this->cron->sendSupPayment();
    }
}
