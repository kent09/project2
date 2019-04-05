<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpirationOnMembershipTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('membership_transactions', 'is_expired')) {
            Schema::table('membership_transactions', function (Blueprint $table) {
                $table->integer('is_expired')->default(0);
            });
        }
        if (!Schema::hasColumn('membership_transactions', 'expired_at')) {
            Schema::table('membership_transactions', function (Blueprint $table) {
                $table->string('expired_at')->nullable()->default('');
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
