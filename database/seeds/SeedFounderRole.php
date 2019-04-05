<?php

use Illuminate\Database\Seeder;
use App\Model\Limitation;
use App\Model\MembershipPrice;

class SeedFounderRole extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $check_founder = Limitation::where('role_id',6)->count();
       if($check_founder == 0){
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'lifetime-membership', 'description' => 'Lifetime Membership', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'limited-slots', 'description' => 'Limited Slots', 'value' => 200, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'task-creation-per-day', 'description' => 'Tasks Creation Per Day', 'value' => 0, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'bot-voting-weight', 'description' => 'Voting Weight', 'value' => 100, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'task-fee-charge', 'description' => 'Task Fee Charge', 'value' => 1, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'hold-day', 'description' => 'Days On Hold Rewards', 'value' => 2, 'type' => 'fixed']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'referral-point', 'description' => 'Referral Task Points', 'value' => 2, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'bot-voting-weight-calculator', 'description' => 'Steemit Voting Weight Calc', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'reputation-renewal', 'description' => 'Reputation  Score Renewal', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'membership-referrer-earned', 'description' => 'Referrer Membership Fee Percentage ', 'value' => 5, 'type' => 'percentage']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'kblog-featured-blogger', 'description' => 'Featured Kblog Blogger', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'task-featured-creator', 'description' => 'Featured Task Creator', 'value' => 1, 'type' => 'boolean']);
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'free-task', 'description' => 'Free Tasks', 'value' => 1000, 'type' => 'fixed']); 
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'attachment-required', 'description' => 'Attachment Required', 'value' => 1, 'type' => 'boolean']); 
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'featured-task', 'description' => 'Featured Tasks', 'value' => 100, 'type' => 'fixed']); 
            Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'gift-membership', 'description' => 'Gift Membership', 'value' => 1, 'type' => 'boolean']); 
       }
       # START SILVER ROLE
       $check_silver1 = Limitation::where('slug','free-task')->where('role_id',2)->count();
       if($check_silver1 == 0){
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'free-task', 'description' => 'Free Tasks', 'value' => 25, 'type' => 'fixed']); 
       }

       $check_silver2 = Limitation::where('slug','attachment-required')->where('role_id',2)->count();
       if($check_silver2 == 0){
            Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'attachment-required', 'description' => 'Attachment Required ', 'value' => 1, 'type' => 'boolean']); 
       }
        # END SILVER ROLE

       # START GOLD ROLE
       $check_gold1 = Limitation::where('slug','free-task')->where('role_id',3)->count();
       if($check_gold1 == 0){
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'free-task', 'description' => 'Free Tasks', 'value' => 50, 'type' => 'fixed']); 
       }

       $check_gold2 = Limitation::where('slug','attachment-required')->where('role_id',3)->count();
       if($check_gold2 == 0){
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'attachment-required', 'description' => 'Attachment Required', 'value' => 1, 'type' => 'boolean']); 
       }

       $check_gold3 = Limitation::where('slug','featured-task')->where('role_id',3)->count();
       if($check_gold3 == 0){
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'featured-task', 'description' => 'Featured Tasks', 'value' => 5, 'type' => 'fixed']); 
       }

       $check_gold4 = Limitation::where('slug','gift-membership')->where('role_id',3)->count();
       if($check_gold4 == 0){
            Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'gift-membership', 'description' => 'Gift Membership', 'value' => 1, 'type' => 'boolean']); 
       }
       # END GOLD ROLE

       # START PLATINUM ROLE
       $check_platinum1 = Limitation::where('slug','free-task')->where('role_id',4)->count();
       if($check_platinum1 == 0){
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'free-task', 'description' => 'Free Tasks', 'value' => 100, 'type' => 'fixed']); 
       }

       $check_platinum2 = Limitation::where('slug','attachment-required')->where('role_id',4)->count();
       if($check_platinum2 == 0){
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'attachment-required', 'description' => 'Attachment Required', 'value' => 1, 'type' => 'boolean']); 
       }

       $check_platinum3 = Limitation::where('slug','featured-task')->where('role_id',4)->count();
       if($check_platinum3 == 0){
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'featured-task', 'description' => 'Featured Tasks', 'value' => 25, 'type' => 'fixed']); 
       }

       $check_platinum4 = Limitation::where('slug','gift-membership')->where('role_id',4)->count();
       if($check_platinum4 == 0){
            Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'gift-membership', 'description' => 'Gift Membership', 'value' => 1, 'type' => 'boolean']); 
       }
        # END PLATINUM ROLE

       $check_founder_mem = MembershipPrice::where('role_id',6)->count();
       if($check_founder_mem == 0){
            MembershipPrice::firstOrCreate(['role_id' => 6, 'amount' => 999.99, 'unit' => 'lifetime']);
       }

       $check_gold_task_fee = Limitation::where('slug','task-fee-charge')->where('role_id',3)->first();
       if($check_gold_task_fee <> null){
            $check_gold_task_fee->value = 2;
            $check_gold_task_fee->save();
       }

       $checker = Limitation::where('slug','attachment-required')->where('role_id',1)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'attachment-required', 'description' => 'Attachment Required', 'value' => 0, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','only-followers-option')->where('role_id',1)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'only-followers-option', 'description' => 'Only Followers Can Complete', 'value' => 0, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','reputation-option')->where('role_id',1)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'reputation-option', 'description' => 'Reputation and Activity Score Option', 'value' => 0, 'type' => 'boolean']);
       }
       
       $checker = Limitation::where('slug','only-connection-option')->where('role_id',1)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'only-connection-option', 'description' => 'Only connection can complete  ', 'value' => 0, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','only-followers-option')->where('role_id',2)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'only-followers-option', 'description' => 'Only Followers Can Complete', 'value' => 1, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','reputation-option')->where('role_id',2)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'reputation-option', 'description' => 'Reputation and Activity Score Option', 'value' => 0, 'type' => 'boolean']);
       }
       
       $checker = Limitation::where('slug','only-connection-option')->where('role_id',2)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 2, 'slug' => 'only-connection-option', 'description' => 'Only connection can complete  ', 'value' => 0, 'type' => 'boolean']);
       }


       $checker = Limitation::where('slug','only-followers-option')->where('role_id',3)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'only-followers-option', 'description' => 'Only Followers Can Complete', 'value' => 0, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','reputation-option')->where('role_id',3)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'reputation-option', 'description' => 'Reputation and Activity Score Option', 'value' => 1, 'type' => 'boolean']);
       }
       
       $checker = Limitation::where('slug','only-connection-option')->where('role_id',3)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 3, 'slug' => 'only-connection-option', 'description' => 'Only connection can complete  ', 'value' => 0, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','only-followers-option')->where('role_id',4)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'only-followers-option', 'description' => 'Only Followers Can Complete', 'value' => 1, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','reputation-option')->where('role_id',4)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'reputation-option', 'description' => 'Reputation and Activity Score Option', 'value' => 1, 'type' => 'boolean']);
       }
       
       $checker = Limitation::where('slug','only-connection-option')->where('role_id',4)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 4, 'slug' => 'only-connection-option', 'description' => 'Only connection can complete  ', 'value' => 1, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','only-followers-option')->where('role_id',6)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'only-followers-option', 'description' => 'Only Followers Can Complete', 'value' => 1, 'type' => 'boolean']);
       }

       $checker = Limitation::where('slug','reputation-option')->where('role_id',6)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'reputation-option', 'description' => 'Reputation and Activity Score Option', 'value' => 1, 'type' => 'boolean']);
       }
       
       $checker = Limitation::where('slug','only-connection-option')->where('role_id',6)->first();
       if($checker == null){
          Limitation::firstOrCreate(['role_id' => 6, 'slug' => 'only-connection-option', 'description' => 'Only connection can complete  ', 'value' => 1, 'type' => 'boolean']);
       }
     }
}
