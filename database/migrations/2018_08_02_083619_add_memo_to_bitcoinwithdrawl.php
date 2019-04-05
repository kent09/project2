<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMemoToBitcoinwithdrawl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('bitcoinwithdrawl', 'memo')) {
            Schema::table('bitcoinwithdrawl', function (Blueprint $table) {
                $table->text('memo');
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
        Schema::table('bitcoinwithdrawl', function (Blueprint $table) {
            //
        });
    }
}
