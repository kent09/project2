<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->default('');
                $table->string('slug')->default('');
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
        Schema::table('permissions', function (Blueprint $table) {
            //
        });
    }
}
