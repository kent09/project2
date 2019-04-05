<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFreeTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_free_tasks')) {
            Schema::create('user_free_tasks', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('task_id')->unsigned();
                $table->integer('user_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->integer('completer_cnt');
                $table->double('task_reward');
                $table->double('task_fee');
                $table->double('total_budget');
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
        Schema::dropIfExists('user_free_tasks');
    }
}
