<?php

namespace App\Console\Commands;

use App\Model\Referral;
use App\Model\ReferralByLevel;
use Illuminate\Console\Command;

class SaveReferralByLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save referrals by level';

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
        $this->info('Saving Referrals by level initiated');

         // First Level
         $referrals_1 = Referral::get(['user_id', 'referrer_id', 'status', 'created_at']);
         $bar = $this->output->createProgressBar(count($referrals_1));
         if (count($referrals_1) > 0) {
             $this->info(count($referrals_1) . ' items fetched');
             foreach ($referrals_1 as $referral_1) {
                 $earner_id_1 = $referral_1->referrer_id;
                 $this->saveReferralByLevel($earner_id_1, $referral_1, 1);
                 // Second Level
                 $referrals_2 = Referral::where('referrer_id', $referral_1->user_id)->get(['user_id', 'referrer_id', 'status','created_at']);
                 $bar2 = $this->output->createProgressBar(count($referrals_2));
                 if (count($referrals_2) > 0) {
                     foreach ($referrals_2 as $referral_2) {
                         $earner_id_2 = $referral_1->referrer_id;
                         $this->saveReferralByLevel($earner_id_2, $referral_2, 2);
                         // Third Level
                         $referrals_3 = Referral::where('referrer_id', $referral_2->user_id)->get(['user_id', 'referrer_id', 'status', 'created_at']);
                         $bar3 = $this->output->createProgressBar(count($referrals_3));
                         if (count($referrals_3) > 0) {
                             foreach ($referrals_3 as $referral_3) {
                                 $earner_id_3 = $referral_1->referrer_id;
                                 $this->saveReferralByLevel($earner_id_3, $referral_3, 3);
                                 $bar3->advance();
                             }
                         }
                         $bar3->finish();
                         $bar2->advance();
                     }
                 }
                 $bar2->finish();
                 $bar->advance();
             }
         } else {
             $this->error('No item fetched');
         }
         $bar->finish();
    }

    private function saveReferralByLevel($earner_id, $referral, $level){
        $checker = ReferralByLevel::where('user_id',$earner_id)->where('referral_id',$referral->user_id)->where('level',$level)->first();
        if($checker == null){
            $referralbylevel = new ReferralByLevel();
            $referralbylevel->referral_id = $referral->user_id;
            $referralbylevel->user_id = $earner_id;
            $referralbylevel->level = $level;
            $referralbylevel->referral_dt = $referral->created_at;
            $referralbylevel->save();
        }
    }
}
