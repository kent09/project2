<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrustedBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('trusted_business')) {
            Schema::create('trusted_business', function (Blueprint $table) {
                $table->increments('id');
                $table->string('business_name',150)->nullable();
                $table->string('business_logo',150)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->integer('admin_id')->nullable();
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
        Schema::dropIfExists('trusted_business');
    }
}
