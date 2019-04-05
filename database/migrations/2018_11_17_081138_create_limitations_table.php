<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLimitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('limitations')) {
            Schema::create('limitations', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('role_id')->unsigned();
                $table->string('slug');
                $table->string('description');
                $table->double('value')->unsigned();
                $table->string('type')->default('fixed');
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
        Schema::dropIfExists('limitations');
    }
}
