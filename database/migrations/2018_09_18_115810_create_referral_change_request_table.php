<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReferralChangeRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        if(!Schema::hasTable('referral_change_request')){
            Schema::create('referral_change_request', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('requestor_id');
                $table->integer('old_referrer_id');
                $table->integer('new_referrer_id');
                $table->tinyInteger('status');
                $table->text('reason');
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
        if(Schema::hasTable('referral_change_request')){
            Schema::dropIfExists('referral_change_request');
        }
    }
}
