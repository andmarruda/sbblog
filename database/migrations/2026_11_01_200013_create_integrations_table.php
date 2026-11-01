<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 16);
            $table->foreignUuid('author_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('article_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->jsonb('config');
            $table->string('placement', 16)->default('head');
            $table->string('consent_category', 16);
            $table->jsonb('page_rule')->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['blog_id', 'scope']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE integrations ADD CONSTRAINT integrations_scope_target_check CHECK (
                (scope = 'blog'    AND author_profile_id IS NULL AND article_id IS NULL) OR
                (scope = 'author'  AND author_profile_id IS NOT NULL AND article_id IS NULL) OR
                (scope = 'article' AND article_id IS NOT NULL AND author_profile_id IS NULL)
            )
        SQL);

        // Raw scripts are global only.
        DB::statement("ALTER TABLE integrations ADD CONSTRAINT integrations_custom_blog_only_check CHECK (provider <> 'custom' OR scope = 'blog')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
