<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Model\MembershipTransaction;

class CheckMembershipValidity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update the validity of membership';

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
        $processing_day = 3;
        $updated = 0;

        $this->info('Membership Validation Start...');
        $this->info('Get all unexpired subscriptions');
        $memberships = MembershipTransaction::where('is_expired', 0)->where('activated_at', '<>', null)->selectRaw('*, SUM(quantity) as sum_quantity')->groupBy('user_id')->get(['id', 'user_id', 'status', 'user_id', 'quantity', 'unit', 'activated_at']);

        if (count($memberships) > 0) {
            $this->info(count($memberships) . ' entries found');
            $bar = $this->output->createProgressBar(count($memberships));
            foreach ($memberships as $membership) {
                if ($membership->status === 0) {
                    $created_date = Carbon::createFromFormat('Y-m-d H:i:s', $membership->activated_at)->timestamp;
                    $now = Carbon::now()->subDays($processing_day)->timestamp;
                    if ($now > $created_date) {
                        $mts = MembershipTransaction::where('user_id', $membership->user_id)->get();
                        if (count($mts) > 0) {
                            foreach ($mts as $mt) {
                                $this->update($mt->id, true);
                                $updated++;
                            }
                        }
                    }
                } elseif ($membership->status === 1) {                    
                    $months = $membership->sum_quantity;
                    if ($membership->unit === 'year') {
                        $months = $months * 12;
                    }
                    $created_date = Carbon::createFromFormat('Y-m-d H:i:s', $membership->activated_at)->timestamp;
                    $now = Carbon::now()->subMonths($months)->timestamp;
                    if ($now > $created_date) {
                        $mts = MembershipTransaction::where('user_id', $membership->user_id)->get();
                        if (count($mts) > 0) {
                            foreach ($mts as $mt) {
                                $this->update($mt->id);
                                $updated++;
                            }
                        }
                    }
                } elseif ($membership->status === 2) {
                    $mts = MembershipTransaction::where('user_id', $membership->user_id)->get();
                    if (count($mts) > 0) {
                        foreach ($mts as $mt) {
                            $this->update($mt->id);
                            $updated++;
                        }
                    }
                }
                $bar->advance();
            }
            $bar->finish();
            $this->line('');
        } else {
            $this->info('No entries found');
        }
        $this->line($updated . ' out of ' . count($memberships) . ' are updated!');
        $this->info('Validation End!');
    }

    private function update($mt_id, $cancel = false)
    {
        $old_mt = MembershipTransaction::find($mt_id);
        if ($old_mt !== null) {
            $old_mt->is_expired = 1;
            $old_mt->expired_at = Carbon::now()->toDateTimeString();
            if ($cancel) {
                $old_mt->status = 3;
            }
            $old_mt->save();
        }
    }
}
