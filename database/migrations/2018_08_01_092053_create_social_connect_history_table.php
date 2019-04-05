<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSocialConnectHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_connect_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('social_id');
            $table->string('account_name',100);
            $table->string('account_id',100);
            $table->integer('status');
            $table->double('version')->default(1);
            $table->text('hard_unlink_reason');
            $table->text('disapproved_reason');
            $table->tinyInteger('hard_unlink_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_connect_history');
    }
}
