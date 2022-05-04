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
        //add a input with a simple description to use as a preview and metatag description
        Schema::table('articles', function(Blueprint $table){
            $table->string('description', 200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove a input with a simple description to use as a preview and metatag description
        Schema::table('articles', function(Blueprint $table){
            $table->dropColumn('description');
        });
    }
};
