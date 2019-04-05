<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTestimonialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        if (!Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title',150)->nullable();
                $table->text('content');
                $table->string('name',150)->nullable();
                $table->integer('admin_id');
                $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('testimonials');
    }
}
