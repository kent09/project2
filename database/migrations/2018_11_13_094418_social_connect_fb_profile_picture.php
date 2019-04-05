<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SocialConnectFbProfilePicture extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('social_connects', 'fb_profile_pic')) {
            Schema::table('social_connects', function (Blueprint $table) {
                $table->string('fb_profile_pic');
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
