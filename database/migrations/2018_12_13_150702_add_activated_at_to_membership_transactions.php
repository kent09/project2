<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActivatedAtToMembershipTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('membership_transactions', 'activated_at')) {
            Schema::table('membership_transactions', function (Blueprint $table) {
                $table->string('activated_at')->nullable()->default(null);
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
        Schema::table('membership_transactions', function (Blueprint $table) {
            //
        });
    }
}
