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
        //adding title, description and if convert automatically to webp
        Schema::table('generals', function(Blueprint $table){
            $table->string('title', 110)->default('SBBlog');
            $table->string('description', 200)->default('SBblog');
            $table->boolean('autoconvert_webp')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove title, description and if convert automatically to webp
        Schema::table('generals', function(Blueprint $table){
            $table->dropColumn('title');
            $table->dropColumn('description');
            $table->dropColumn('autoconvert_webp');
        });
    }
};
