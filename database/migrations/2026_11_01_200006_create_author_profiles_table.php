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
        Schema::create('author_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('slug');
            $table->text('bio')->nullable();
            $table->foreignUuid('avatar_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['blog_id', 'slug']);
            // A user has at most one profile per blog; guest authors (user_id null) are unlimited.
            $table->unique(['blog_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_profiles');
    }
};
