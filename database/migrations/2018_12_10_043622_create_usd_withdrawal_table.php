<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsdWithdrawalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('membership_withdrawals')) {
            Schema::create('membership_withdrawals', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->double('amount')->default(0);
                $table->string('type')->nullable()->default('');
                $table->string('btc_address')->nullable()->default('');
                $table->string('paypal_email')->nullable()->default('');
                $table->string('ref_number')->nullable()->default('');
                $table->string('email_token')->nullable()->default('');
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
        Schema::dropIfExists('membership_withdrawals');
    }
}
