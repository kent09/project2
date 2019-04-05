<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class RecalculateReferralReward extends Command
{
    protected $cron;
    public $output;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate Referral Reward';

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
        $this->cron->recalculateReferralReward($this);
    }
}
