<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment_configs', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
            $table->bigInteger('language_id');
            $table->string('description');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('RESTRICT')->onUpdate('CASCADE');
            $table->boolean('is_public')->default(true);
        });

        Artisan::call(
            'db:seed',
            [
                '--class' => 'CommentConfigSeeder',
                '--force' => true
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comment_configs');
    }
};
