<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSessionDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_session_detail', function (Blueprint $table) {
            $table->increments('id');
            $table->string('session_id');
            $table->string('user_agent');
            $table->dateTime('login_date');
            $table->dateTime('logout_date');
            $table->timestamps();
        });
        if (Schema::connection('mysql')->hasColumn('sessions', 'user_agent')) {
            Schema::connection('mysql')->table('sessions', function (Blueprint $table) {
                $table->dropColumn('user_agent');
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
        if (!Schema::connection('mysql')->hasColumn('sessions', 'user_agent')) {
            Schema::connection('mysql')->table('sessions', function (Blueprint $table) {
                $table->addColumn('string', 'user_agent');
            });
        }
        Schema::dropIfExists('user_session_detail');
    }
}
