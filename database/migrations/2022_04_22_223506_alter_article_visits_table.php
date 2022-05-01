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
        //add hash column
        Schema::table('article_visits', function(Blueprint $table){
            $table->string('visit_hash', 32);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove hash column
        Schema::table('article_visits', function(Blueprint $table){
            $table->dropColumn('visit_hash');
        });
    }
};
