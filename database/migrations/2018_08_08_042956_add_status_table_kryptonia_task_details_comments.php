<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusTableKryptoniaTaskDetailsComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('kryptonia_task_comment_detail', 'status')) {
            Schema::table('kryptonia_task_comment_detail', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1);
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
        Schema::table('kryptonia_task_comment_detail', function (Blueprint $table) {
            //
        });
    }
}
