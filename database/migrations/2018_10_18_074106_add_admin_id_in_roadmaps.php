<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdminIdInRoadmaps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        if (!Schema::hasColumn('roadmaps', 'admin_id')){
            Schema::table('roadmaps', function (Blueprint $table) {
                $table->integer('admin_id')->nullable();
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
        if (Schema::hasColumn('roadmaps', 'admin_id')) {
            Schema::table('roadmaps', function (Blueprint $table) {
                $table->dropColumn('admin_id');
            });
        }
    }
}
