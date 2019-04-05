<?php

use App\Model\Limitation;
use Illuminate\Database\Seeder;

class SeedBronzeMembershipReferrerEarned extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $limitations_count = Limitation::where('slug', 'membership-referrer-earned')->count();
        if ($limitations_count === 0) {
            Limitation::firstOrCreate(['role_id' => 1, 'slug' => 'membership-referrer-earned', 'description' => 'Referrer Membership Fee Percentage ', 'value' => 0.50, 'type' => 'percentage']);
        }
    }
}
