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
        //adding configuration of comment
        Schema::table('generals', function(Blueprint $table){
            $table->bigInteger('comment_config_id')->default(1);
            $table->foreign('comment_config_id')->references('id')->on('comment_configs')->onDelete('RESTRICT')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //drop foreign key and comment configuration
        Schema::table('generals', function(Blueprint $table){
            $table->dropForeign('comment_config_id');
            $table->dropColumn('comment_config_id');
        });
    }
};
