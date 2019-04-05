<?php

use App\Model\Role;
use Illuminate\Database\Seeder;

class SeedRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate(['name' => 'Bronze', 'slug' => 'bronze', 'user_type' => 0]);
        Role::firstOrCreate(['name' => 'Silver', 'slug' => 'silver', 'user_type' => 1]);
        Role::firstOrCreate(['name' => 'Gold', 'slug' => 'gold', 'user_type' => 2]);
        Role::firstOrCreate(['name' => 'Platinum', 'slug' => 'platinum', 'user_type' => 3]);
        Role::firstOrCreate(['name' => 'Admin', 'slug' => 'admin', 'user_type' => 9]);
        Role::firstOrCreate(['name' => 'Founder', 'slug' => 'founder', 'user_type' => 4]);
    }
}
