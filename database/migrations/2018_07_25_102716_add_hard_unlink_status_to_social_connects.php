<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHardUnlinkStatusToSocialConnects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('social_connects', 'hard_unlink_status')) {
            Schema::table('social_connects', function (Blueprint $table) {
                $table->tinyInteger('hard_unlink_status')->default(0);
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
