<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralTaskPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('referral_task_points')) {
            Schema::create('referral_task_points', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('referral_id')->unsigned();
                $table->integer('task_id')->unsigned();
                $table->double('points')->default(0);
                $table->integer('level')->unsigned()->default(1);
                $table->integer('fixed')->default(0);
                $table->integer('version')->default(1);
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
        Schema::dropIfExists('referral_task_points');
    }
}
