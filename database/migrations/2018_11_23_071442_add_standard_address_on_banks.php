<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStandardAddressOnBanks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('banks', 'standard_address')) {
            Schema::table('banks', function (Blueprint $table) {
                $table->string('standard_address')->default('');
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
        Schema::table('banks', function (Blueprint $table) {
            //
        });
    }
}
