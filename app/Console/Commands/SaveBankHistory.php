<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repository\CronRepository;

class SaveBankHistory extends Command
{
    public $output;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save bank history';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cron = new CronRepository;
        $cron->saveBankHistory($this);
    }
}
