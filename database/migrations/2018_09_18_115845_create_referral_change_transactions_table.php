<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferralChangeTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('referral_change_transactions')){
            Schema::create('referral_change_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('referral_tbl_id');
                $table->integer('referral_req_id');
                $table->integer('admin_id');
                $table->text('decline_reason');
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
        if(Schema::hasTable('referral_change_transactions')){
            Schema::dropIfExists('referral_change_transactions');
        }
    }
}
