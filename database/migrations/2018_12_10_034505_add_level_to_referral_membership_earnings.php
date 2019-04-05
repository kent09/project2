<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLevelToReferralMembershipEarnings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('referral_membership_earnings', 'level')) {
            Schema::table('referral_membership_earnings', function (Blueprint $table) {
                $table->integer('level')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referral_membership_earnings', function (Blueprint $table) {
            //
        });
    }
}
