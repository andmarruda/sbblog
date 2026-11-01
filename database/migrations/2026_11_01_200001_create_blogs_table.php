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
        Schema::create('blogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('canonical_url')->nullable();
            $table->string('title')->nullable();
            $table->string('slogan')->nullable();
            $table->string('description', 500)->nullable();
            // FK added by the media migration (circular reference).
            $table->uuid('brand_media_id')->nullable();
            $table->foreignUuid('default_language_id')->constrained('languages')->restrictOnDelete();
            $table->string('comment_policy', 32)->default('moderated');
            $table->boolean('autoconvert_webp')->default(true);
            $table->unsignedBigInteger('event_sequence')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('blog_languages', function (Blueprint $table) {
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('language_id')->constrained()->restrictOnDelete();

            $table->primary(['blog_id', 'language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_languages');
        Schema::dropIfExists('blogs');
    }
};
