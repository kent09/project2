<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnReasonBannedUserTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasColumn('banned_user_task', 'reason') ) {
            Schema::table('banned_user_task', function (Blueprint $table) {
                //
                $table->addColumn('text', 'reason');
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
        if ( Schema::hasColumn('banned_user_task', 'reason') ) {
            Schema::table('banned_user_task', function (Blueprint $table) {
                //
                $table->dropColumn('reason');
            });
        }
    }
}
