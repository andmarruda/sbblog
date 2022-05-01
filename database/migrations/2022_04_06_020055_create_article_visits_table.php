<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_visits', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->timeStampTz('load_datetime')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->ipAddress('ip_address');
            $table->text('user_agent');
            $table->jsonb('user_details');
            $table->bigInteger('article_id');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('RESTRICT')->onUpdate('CASCADE');
            $table->timestampTz('unload_datetime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_visits');
    }
};
