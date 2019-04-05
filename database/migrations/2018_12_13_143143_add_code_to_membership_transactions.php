<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCodeToMembershipTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('membership_transactions', 'code')) {
            Schema::table('membership_transactions', function (Blueprint $table) {
                $table->string('code')->nullable()->default(null);
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
