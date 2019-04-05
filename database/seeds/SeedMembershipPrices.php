<?php

use App\Model\MembershipPrice;
use Illuminate\Database\Seeder;

class SeedMembershipPrices extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $prices_count = MembershipPrice::count();
        if ($prices_count === 0) {
            MembershipPrice::firstOrCreate(['role_id' => 1, 'amount' => 0, 'unit' => 'month']);
            MembershipPrice::firstOrCreate(['role_id' => 2, 'amount' => 9.99, 'unit' => 'month']);
            MembershipPrice::firstOrCreate(['role_id' => 3, 'amount' => 24.99, 'unit' => 'month']);
            MembershipPrice::firstOrCreate(['role_id' => 4, 'amount' => 479.99, 'unit' => 'year']);
            MembershipPrice::firstOrCreate(['role_id' => 6, 'amount' => 999.99, 'unit' => 'year']);
        }
    }
}
