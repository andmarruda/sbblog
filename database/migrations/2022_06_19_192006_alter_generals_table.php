<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //adding column for id of google analytics
        Schema::table('generals', function(Blueprint $table){
            $table->string('google_analytics')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove column for id of google analytics
        Schema::table('generals', function(Blueprint $table){
            $table->dropColumn('google_analytics');
        });
    }
};
