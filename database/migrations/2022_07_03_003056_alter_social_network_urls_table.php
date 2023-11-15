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
        //droping and recreating unique key
        Schema::table('social_network_urls', function(Blueprint $table) {
            $table->dropUnique('social_network_id');
            $table->unique(['general_id', 'social_network_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //reversing change
        Schema::table('social_network_urls', function(Blueprint $table) {
            $table->dropUnique('social_network_urls_general_id_social_network_id_unique');
            $table->unique('general_id', 'social_network_id');
        });
    }
};
