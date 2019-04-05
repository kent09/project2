<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('membership_transactions')) {
            Schema::create('membership_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->double('quantity');
                $table->double('amount');
                $table->string('unit');
                $table->string('payment_method')->default('paypal');
                $table->string('payment_id');
                $table->string('token')->default('');
                $table->string('payer_id')->default('');
                $table->integer('status')->default(0);
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
        Schema::dropIfExists('membership_transactions');
    }
}
