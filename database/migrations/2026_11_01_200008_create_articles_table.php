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
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_profile_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('language_id')->constrained()->restrictOnDelete();
            $table->uuid('translation_group_id');
            $table->string('slug');
            $table->string('status', 16)->default('draft');
            // FKs added by the article_revisions migration (circular reference).
            $table->uuid('current_revision_id')->nullable();
            $table->uuid('published_revision_id')->nullable();
            $table->string('bgcolor', 32)->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['blog_id', 'language_id', 'slug']);
            $table->unique(['translation_group_id', 'language_id']);
            $table->index(['blog_id', 'status', 'published_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
