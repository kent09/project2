<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultsToFaqsHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('faqs_histories')) {
            Schema::table('faqs_histories', function (Blueprint $table) {
                $table->dateTime('deleted_at')->nullable()->default(null)->change();
                $table->integer('deleted_by')->nullable()->unsigned()->change();
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
        Schema::table('faqs_histories', function (Blueprint $table) {
            //
        });
    }
}
