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
        Schema::create('social_network_urls', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->bigInteger('general_id');
            $table->foreign('general_id')->references('id')->on('generals')->onDelete('RESTRICT')->onUpdate('CASCADE');
            $table->bigInteger('social_network_id');
            $table->foreign('social_network_id')->references('id')->on('social_networks')->onDelete('RESTRICT')->onUpdate('CASCADE');
            $table->string('url', 250);
            $table->unique('general_id', 'social_network_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_network_urls');
    }
};
