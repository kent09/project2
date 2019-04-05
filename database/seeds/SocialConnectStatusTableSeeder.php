<?php

use Illuminate\Database\Seeder;
use App\Model\SocialConnectStatus;
class SocialConnectStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

         SocialConnectStatus::firstOrCreate(['status' => 'linked', 'description' => 'unlink']);
         SocialConnectStatus::firstOrCreate(['status' => 'soft-unlinked', 'description' => 'link']);
         SocialConnectStatus::firstOrCreate(['status' => 'soft-unlinked', 'description' => 'Request for hard Unlink']);
         SocialConnectStatus::firstOrCreate(['status' => 'hard-unlinked', 'description' => 'approved']);
         
       
    }
}
