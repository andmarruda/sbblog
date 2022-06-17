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
        //adding column premiere_date timestamptz
        Schema::table('articles', function(Blueprint $table){
            $table->timestamptz('premiere_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //dropping column premiere_date
        Schema::table('articles', function(Blueprint $table){
            $table->dropColumn('premiere_date');
        });
    }
};
