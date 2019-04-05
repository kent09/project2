<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankTransactionHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_transaction_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->integer('trxn_id');
            $table->enum('trxn_type', ['deposit', 'withdrawal']);	
            $table->dateTime('trxn_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('ending_balance', 10, 2);
            $table->integer('status');
            $table->string('tx_id', 100);
            $table->string('payment_id', 100);
            $table->string('address', 100);
            $table->integer('block');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_transaction_histories');
    }
}
