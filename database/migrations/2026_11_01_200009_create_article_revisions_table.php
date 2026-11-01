<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->jsonb('body');
            $table->foreignUuid('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by_app_installation_id')->nullable()->constrained('app_installations')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['article_id', 'number']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('article_revisions')->nullOnDelete();
            $table->foreign('published_revision_id')->references('id')->on('article_revisions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
            $table->dropForeign(['published_revision_id']);
        });

        Schema::dropIfExists('article_revisions');
    }
};
