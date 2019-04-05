<?php

namespace App\Console\Commands;

use App\Model\Referral;
use App\Model\Settings;
use App\Model\TaskUser;
use App\ReferralTaskPoint;
use Illuminate\Console\Command;

class CalculateReferralPointsV2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:points {--legacy : For Version 1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Version 2 of Referral Task Points Calculation';

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
        $this->info('Referral Point Calculation V2 Initiated');
        $this->line('Fetching All Referral List');
        if ($this->option('legacy')) {
            $this->info('Using Legacy Points Calculations');
            $version = 1;
        } else {
            $this->info('Using New Points Calculations');
            $version = 2;
        }

        // First Level
        $referrals_1 = Referral::get(['user_id', 'referrer_id', 'status']);
        $bar = $this->output->createProgressBar(count($referrals_1));
        if (count($referrals_1) > 0) {
            $this->info(count($referrals_1) . ' items fetched');
            foreach ($referrals_1 as $referral_1) {
                $earner_id_1 = $referral_1->referrer_id;
                $this->processValidation($earner_id_1, $referral_1, 1, $version);
                // Second Level
                $referrals_2 = Referral::where('referrer_id', $referral_1->user_id)->get(['user_id', 'referrer_id', 'status']);
                $bar2 = $this->output->createProgressBar(count($referrals_2));
                if (count($referrals_2) > 0) {
                    foreach ($referrals_2 as $referral_2) {
                        $earner_id_2 = $referral_2->referrer_id;
                        $this->processValidation($earner_id_2, $referral_2, 2, $version);
                        // Third Level
                        $referrals_3 = Referral::where('referrer_id', $referral_2->user_id)->get(['user_id', 'referrer_id', 'status']);
                        $bar3 = $this->output->createProgressBar(count($referrals_3));
                        if (count($referrals_3) > 0) {
                            foreach ($referrals_3 as $referral_3) {
                                $earner_id_3 = $referral_3->referrer_id;
                                $this->processValidation($earner_id_3, $referral_3, 3, $version);
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
        $this->info('Calculation Ended');
    }

    private function processValidation($earner_id, $referral, $level, $version)
    {
        $task_users = TaskUser::with(['taskPoints' => function ($q) {
            $q->where('fixed', 0);
        }])->where('user_id', $referral->user_id)->get();
        if (count($task_users) > 0) {
            foreach ($task_users as $task_user) {
                $saved = ReferralTaskPoint::where('user_id', $earner_id)->where('referral_id', $referral->user_id)->where('task_id', $task_user->task_id)->select('fixed')->first();
                $save_it = false;
                if ($saved === null) {
                    $save_it = true;
                } else {
                    if ($saved->version === $version) {
                        if ($saved->fixed === 0) {
                            $save_it = true;
                        }
                    }
                }
                if ($save_it) {
                    $this->saveData($earner_id, $referral, $task_user, $level, $version);
                }
            }
        }
    }

    private function getPoint($earner_id, $user_id, $task_id, $level)
    {
        $points = 0;
        if ($this->option('legacy')) {
            $point_system = Settings::where('key', 'referral_point_system')->first();
            $point = Settings::where('key', 'referral_point')->first();
            if ($point_system) {
                if ($point) {
                    $reward = TaskUser::where('user_id', $user_id)->where('task_id', $task_id)->where('revoke', 0)->sum('reward');
                    if ($point_system->value == 'predefined') {
                        $points = (double) $point->value;
                    } elseif ($point_system->value == 'percentage') {
                        $points = (double) ($reward * ($point->value / 100));
                    }
                }
            }
        } else {
            $limitation = limitation_info('referral-point', $earner_id);
            if ($limitation['value'] !== null) {
                $reward = TaskUser::where('user_id', $user_id)->where('task_id', $task_id)->where('revoke', 0)->sum('reward');
                if ($limitation['type'] == 'predefined') {
                    $points = (double)$limitation['value'];
                } elseif ($limitation['type'] == 'percentage') {
                    $points = (double)($reward * ($limitation['value'] / 100));
                }
            }
        }

        if ($level === 2) {
            $points = $points * .50;
        } elseif ($level === 3) {
            $points = $points * .25;
        }
        return $points;
    }

    private function saveData($earner_id, $referral, $task_user, $level, $version)
    {
        $saved = new ReferralTaskPoint;
        $saved->user_id = $earner_id;
        $saved->referral_id = $referral->user_id;
        $saved->task_id = $task_user->task_id;
        $saved->points = $this->getPoint($earner_id, $referral->user_id, $task_user->task_id, $level);
        $saved->level = $level;
        $saved->fixed = $task_user->taskInfo->getStatusStrAttribute() === 'active' ? 0 : 1;
        $saved->version = $version;
        $saved->save();
    }
}
