<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	// $this->call(BankTransactionsTableSeeder::class);
        $this->call(SocialConnectStatusTableSeeder::class);
        $this->call(SocialMediasTableSeeder::class);
        $this->call(SeedRoles::class);
        $this->call(SeedPermissions::class);
        $this->call(SeedRolePermissions::class);
        $this->call(SeedLimitations::class);
        $this->call(SeedMembershipPrices::class);
        $this->call(SeedFounderRole::class);
        $this->call(SeedBronzeMembershipReferrerEarned::class);
    }
}
