<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ArticleComments;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->softDeletesTz();
        });

        ArticleComments::where('active', 0)->delete();

        Schema::table('article_comments', function (Blueprint $table) {
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
        Schema::table('article_comments', function (Blueprint $table) {
            $table->boolean('active');
        });

        ArticleComments::whereNotNull('deleted_at')->update(['active' => 0]);

        Schema::table('article_comments', function (Blueprint $table) {
            $table->dropSoftDeletesTz();
        });
    }
};
