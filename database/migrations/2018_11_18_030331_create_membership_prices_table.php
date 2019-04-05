<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('membership_prices')) {
            Schema::create('membership_prices', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('role_id')->unsigned();
                $table->double('amount');
                $table->string('unit');
                $table->integer('status')->default(1);
                $table->timestamps();
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
        Schema::dropIfExists('membership_prices');
    }
}
