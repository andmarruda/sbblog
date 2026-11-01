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
        Schema::create('article_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('article_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('base_revision_id')->constrained('article_revisions')->cascadeOnDelete();
            $table->foreignUuid('proposed_revision_id')->unique()->constrained('article_revisions')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->string('source', 16)->default('human');
            $table->foreignUuid('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('requested_by_app_installation_id')->nullable()->constrained('app_installations')->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->index(['article_id', 'status']);
        });

        Schema::create('suggestion_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('suggestion_id')->constrained('article_suggestions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->jsonb('anchor')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_comments');
        Schema::dropIfExists('article_suggestions');
    }
};
