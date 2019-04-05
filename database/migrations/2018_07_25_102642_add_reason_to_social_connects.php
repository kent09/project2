<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReasonToSocialConnects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('social_connects', 'reason')) {
            Schema::table('social_connects', function (Blueprint $table) {
                $table->text('reason');
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
