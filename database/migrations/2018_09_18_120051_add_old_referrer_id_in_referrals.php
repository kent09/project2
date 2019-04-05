<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOldReferrerIdInReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('referrals', 'old_referrer_id')){
            Schema::table('referrals', function (Blueprint $table) {
                $table->integer('old_referrer_id');
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
        if (Schema::hasColumn('referrals', 'old_referrer_id')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->dropColumn('old_referrer_id');
            });
        }
    }
}
