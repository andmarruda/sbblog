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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('article_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('author_name');
            $table->string('author_email_hash', 128)->nullable();
            $table->text('body');
            $table->string('status', 16)->default('pending');
            $table->boolean('is_public')->default(false);
            $table->string('ip_hash', 128)->nullable();
            $table->foreignUuid('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('moderated_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['article_id', 'status']);
            $table->index(['blog_id', 'status', 'created_at']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
