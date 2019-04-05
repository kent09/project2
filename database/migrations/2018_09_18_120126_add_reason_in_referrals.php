<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReasonInReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('referrals', 'reason')){
            Schema::table('referrals', function (Blueprint $table) {
                $table->text('reason')->nullable();
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
        if (Schema::hasColumn('referrals', 'reason')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
}
