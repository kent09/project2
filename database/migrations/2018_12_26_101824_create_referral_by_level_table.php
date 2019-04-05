<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferralByLevelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('referral_by_level')) {
            Schema::create('referral_by_level', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('referral_id');
                $table->integer('level');
                $table->dateTime('referral_dt');
                $table->timestamps();
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
        Schema::dropIfExists('referral_by_level');
    }
}
