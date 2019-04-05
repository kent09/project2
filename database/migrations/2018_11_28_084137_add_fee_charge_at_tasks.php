<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFeeChargeAtTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('tasks', 'fee_charge')){
            Schema::table('tasks', function (Blueprint $table) {
                $table->double('fee_charge', 8, 2);
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
        if (Schema::hasColumn('tasks', 'fee_charge')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('fee_charge');
            });
        }
    }
}
