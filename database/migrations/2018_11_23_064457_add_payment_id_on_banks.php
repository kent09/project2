<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentIdOnBanks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('banks', 'payment_id')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->string('payment_id')->default('');
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
        Schema::table('banks', function (Blueprint $table) {
            //
        });
    }
}
