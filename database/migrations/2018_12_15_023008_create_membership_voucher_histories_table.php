<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMembershipVoucherHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('membership_voucher_histories')) {
            Schema::create('membership_voucher_histories', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('payer_id')->unsigned();
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('trans_id')->unsigned();
                $table->string('code')->nullable()->default(null);
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
        Schema::dropIfExists('membership_voucher_histories');
    }
}
