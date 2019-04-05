<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaderBoardOwnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('leader_board_own')) {
            Schema::create('leader_board_own', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->text('referral_count')->nullable();
                $table->integer('all_time_rank');
                $table->integer('monthly_rank');
                $table->integer('weekly_rank');
                $table->longText('all_time_list')->nullable();
                $table->longText('monthly_list')->nullable();
                $table->longText('weekly_list')->nullable();
                $table->longText('direct_referral_list')->nullable();
                $table->longText('second_referral_list')->nullable();
                $table->longText('third_referral_list')->nullable();
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
        Schema::dropIfExists('leader_board_own');
    }
}
