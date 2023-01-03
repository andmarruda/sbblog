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
        //adding if comment was sended at public and if has been authorized
        Schema::table('article_comments', function(Blueprint $table){
            $table->boolean('comment_public')->default(true);
            $table->boolean('authorized_public')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //removing if comment was sended at public and if has been authorized
        Schema::table('article_comments', function(Blueprint $table){
            $table->dropColumn('comment_public');
            $table->dropColumn('authorized_public');
        });
    }
};
