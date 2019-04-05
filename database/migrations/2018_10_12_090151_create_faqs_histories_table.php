<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaqsHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        Schema::create('faqs_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category',150);
            $table->string('title',150);
            $table->text('content');
            $table->integer('admin_id');
            $table->tinyInteger('is_deleted')->default(0);
            $table->dateTime('deleted_at');
            $table->integer('deleted_by');
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
        Schema::dropIfExists('faqs_histories');
    }
}
