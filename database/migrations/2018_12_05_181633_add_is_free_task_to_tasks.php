<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFreeTaskToTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('tasks', 'is_free_task')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->tinyInteger('is_free_task')->default(0);
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
        if (Schema::hasColumn('tasks', 'is_free_task')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('is_free_task');
            });
        }
    }
}
