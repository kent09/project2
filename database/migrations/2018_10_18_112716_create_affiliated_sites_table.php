<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAffiliatedSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('affiliated_sites')) {
            Schema::create('affiliated_sites', function (Blueprint $table) {
                $table->increments('id');
                $table->string('site_name',150)->nullable();
                $table->string('site_url',150)->nullable();
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
        Schema::dropIfExists('affiliated_sites');
    }
}
