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
        //add preferred language
        Schema::table('users', function(Blueprint $table){
            $table->integer('language_id')->default(1);
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('RESTRICT')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //drop column about preferred language
        Schema::table('users', function(Blueprint $table){
            $table->dropForeign('users_languages_language_id_foreign');
            $table->dropColumn('language_id');
        });
    }
};
