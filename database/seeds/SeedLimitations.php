<?php

use App\Model\Limitation;
use Illuminate\Database\Seeder;

class SeedLimitations extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limitations_count = Limitation::count();
        if ($limitations_count === 0) {
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'task-creation-per-day', 'description' => 'Tasks Creation Per Day', 'value' => 4, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'bot-voting-weight', 'description' => 'Voting Weight', 'value' => 25, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'task-fee-charge', 'description' => 'Task Fee Charge', 'value' => 5, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'hold-day', 'description' => 'Days On Hold Rewards', 'value' => 14, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'referral-point', 'description' => 'Referral Task Points', 'value' => 0.50, 'type' => 'percentage']);
     
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'task-creation-per-day', 'description' => 'Tasks Creation Per Day', 'value' => 10, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'bot-voting-weight', 'description' => 'Voting Weight', 'value' => 50, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'task-fee-charge', 'description' => 'Task Fee Charge', 'value' => 3, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'hold-day', 'description' => 'Days On Hold Rewards', 'value' => 7, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'referral-point', 'description' => 'Referral Task Points', 'value' => 1, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'bot-voting-weight-calculator', 'description' => 'Steemit Voting Weight Calc', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'reputation-renewal', 'description' => 'Reputation  Score Renewal', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'membership-referrer-earned', 'description' => 'Referrer Membership Fee Percentage ', 'value' => 2, 'type' => 'percentage']);
 
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'task-creation-per-day', 'description' => 'Tasks Creation Per Day', 'value' => 20, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'bot-voting-weight', 'description' => 'Voting Weight', 'value' => 75, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'task-fee-charge', 'description' => 'Task Fee Charge', 'value' => 4, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'hold-day', 'description' => 'Days On Hold Rewards', 'value' => 4, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'referral-point', 'description' => 'Referral Task Points', 'value' => 1.50, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'bot-voting-weight-calculator', 'description' => 'Steemit Voting Weight Calc', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'reputation-renewal', 'description' => 'Reputation  Score Renewal', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'membership-referrer-earned', 'description' => 'Referrer Membership Fee Percentage ', 'value' => 3, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'kblog-featured-blogger', 'description' => 'Featured Kblog Blogger', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'task-featured-creator', 'description' => 'Featured Task Creator', 'value' => 1, 'type' => 'boolean']);

            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'task-creation-per-day', 'description' => 'Tasks Creation Per Day', 'value' => 0, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'bot-voting-weight', 'description' => 'Voting Weight', 'value' => 100, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'task-fee-charge', 'description' => 'Task Fee Charge', 'value' => 1, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'hold-day', 'description' => 'Days On Hold Rewards', 'value' => 2, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'referral-point', 'description' => 'Referral Task Points', 'value' => 2, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'bot-voting-weight-calculator', 'description' => 'Steemit Voting Weight Calc', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'reputation-renewal', 'description' => 'Reputation  Score Renewal', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'membership-referrer-earned', 'description' => 'Referrer Membership Fee Percentage ', 'value' => 5, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'kblog-featured-blogger', 'description' => 'Featured Kblog Blogger', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'task-featured-creator', 'description' => 'Featured Task Creator', 'value' => 1, 'type' => 'boolean']);

        }
    }
}
