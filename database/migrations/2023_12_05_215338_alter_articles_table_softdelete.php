<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Article;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('articles', function(Blueprint $table){
            $table->softDeletesTz();
        });

        Article::where('active', false)->delete();

        Schema::table('articles', function(Blueprint $table) {
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('articles', function(Blueprint $table) {
            $table->boolean('active')->default(true);
        });
        
        Article::whereNotNull('deleted_at')->update(['active' => false]);

        Schema::table('articles', function(Blueprint $table){
            $table->dropSoftDeletesTz();
        });
    }
};
