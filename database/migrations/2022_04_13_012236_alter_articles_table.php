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
        //add column url_friendly | The article title for URL
        Schema::table('articles', function(Blueprint $table){
            $table->string('url_friendly', 150);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove column url_friendly | The article title for URL
        Schema::table('articles', function(Blueprint $table){
            $table->dropColumn('url_friendly');
        });
    }
};
