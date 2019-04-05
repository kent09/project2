<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHardUnlinkRequestAtSocialConnectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('social_connects', 'hard_unlink_request_at')) {
            Schema::table('social_connects', function (Blueprint $table) {
                $table->dateTime('hard_unlink_request_at');
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
        Schema::table('social_connects', function (Blueprint $table) {
            //
        });
    }
}
