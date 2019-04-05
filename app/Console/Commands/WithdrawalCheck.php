<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\DbWithdrawal;
use App\Model\EtherWithdrawl;
use DateTime;

class WithdrawalCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdrawal:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checking Unverified Withdrawal for Only 24 Hours';

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
        $this->line('Checking...');

        $dbwithdrawal = new DbWithdrawal;
        $etherwithdrawal = new EtherWithdrawl;
        $withdrawals = $dbwithdrawal->where('status', 0)->get();
        $ether_withdrawals = $etherwithdrawal->where('status', 0)->get();
        
        if (count($withdrawals)>0) {
            $this->loop($withdrawals, $dbwithdrawal);
        }
        if (count($ether_withdrawals)>0) {
            $this->loop($ether_data, $etherwithdrawal);
        }

    }

    public function loop($withdrawals, $model) {

        $count = 0;
        foreach ($withdrawals as $item){
            $start = new DateTime($item->created_at);
            $today = new DateTime(date('Y-m-d H:i:s'));

            $interval = $today->diff($start);
            $day = $interval->format('%a');
            if ($day >= 1){
                $new_db_withdrawal = $model->find($item->id);
                $new_db_withdrawal->status = 9;
                $new_db_withdrawal->save();
                $count++;

            }
            $this->line("Updating...");
        }   
        $this->line("{$count} Row(s) Updated.");
    }


}
