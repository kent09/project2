<?php

namespace App\Console\Commands;

use App\User;
use Carbon\Carbon;
use Monero\Wallet;
use App\Model\Bank;
use App\Model\RecTxid;
use Illuminate\Console\Command;

class GetExternalDeposits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deposit:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Basic External Deposits';

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
        $this->info('Starting to process deposits');
        $this->info('Initiating Wallet...');
        $host = config('app.wallet.ip');
        $wallet = new Wallet($host);
        $this->info('Wallet initiated');
        
        $this->info('Get all banks');
        $banks = Bank::all();
        if (count($banks) > 0) {
            $deposits = 0;
            $success = 0;
            $bar = $this->output->createProgressBar(count($banks));
            $start = Carbon::now();
            foreach ($banks as $bank) {
                $user = User::find($bank->user_id);
                if ($user !== null) {
                    $splitIntegrated = $wallet->splitIntegratedAddress($bank->address);
                    $splitIntegrated = json_decode($splitIntegrated);
                    
                    if ($splitIntegrated !== '') {
                        $payments = $wallet->getPayments($splitIntegrated->payment_id);
                        $payments = json_decode($payments);
                        
                        if ($payments !== []) {
                            if (isset($payments->payments)) {
                                $payments = $payments->payments;
                                foreach ($payments as $payment) {
                                    $entry_found = RecTxid::where('txid', $payment->tx_hash)->first();
                                    if ($entry_found === null) {
                                        sleep(1);
                                        $stats = file_get_contents('http://superior-coin.info:8081/api/transaction/'.$payment->tx_hash);
                                        $stats = json_decode($stats);
                                        $stats = $stats->data;

                                        $txid = $stats->tx_hash;
                                        $timestamps = $stats->timestamp_utc;
                                        $height = $stats->block_height;

                                        // FOR DOUBLE CHECKING
                                        $entry_found_again = RecTxid::where('txid', $txid)->first();
                                        if ($entry_found_again === null) {
                                            $rec_txids = new RecTxid;
                                            if ($height >= 10) {
                                                $rec_txids->status = 1;
                                            }else{
                                                $rec_txids->status = 0;
                                            }
                                            $rec_txids->user_id = $user->id;
                                            $rec_txids->recadd = $bank->address;
                                            $rec_txids->txid = $txid;
                                            $rec_txids->date = $timestamps;
                                            $rec_txids->height = $height;
                                            $rec_txids->coins = $payment->amount / 100000000;
                                            $rec_txids->save();
    
                                            $success++;
                                        }
                                    }
                                    $deposits++;
                                }
                            }
                        }
                    }
                }
                $bar->advance();
            }
            $end = Carbon::now();
            $total_run = $start->diffInSeconds($end);
            $run_time = gmdate('H:i:s', $total_run);
            $bar->finish();
            $this->info('Total run: ' . $run_time);
            $this->info('Processed ' . $success . ' out of ' . $deposits . ' deposits from ' . count($banks) . ' banks');
        } else {
            $this->info('No Banks found');
        }
        $this->info('Process end!');
    }
}
