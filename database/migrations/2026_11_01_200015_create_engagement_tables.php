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

        // Raw views are partitioned by month. Monthly partitions are created by a scheduled job;
        // the default partition catches anything outside them.
        DB::statement(<<<'SQL'
            CREATE TABLE page_views (
                id bigint GENERATED ALWAYS AS IDENTITY,
                blog_id uuid NOT NULL REFERENCES blogs (id) ON DELETE CASCADE,
                article_id uuid NULL REFERENCES articles (id) ON DELETE CASCADE,
                author_profile_id uuid NULL REFERENCES author_profiles (id) ON DELETE SET NULL,
                frontend_id uuid NULL REFERENCES frontends (id) ON DELETE SET NULL,
                visitor_hash varchar(128) NOT NULL,
                referrer_host varchar(255) NULL,
                country char(2) NULL,
                device varchar(16) NULL,
                duration_ms integer NULL,
                viewed_at timestamptz NOT NULL DEFAULT now(),
                PRIMARY KEY (id, viewed_at)
            ) PARTITION BY RANGE (viewed_at)
        SQL);
        DB::statement('CREATE TABLE page_views_default PARTITION OF page_views DEFAULT');
        DB::statement('CREATE INDEX page_views_blog_id_viewed_at_index ON page_views (blog_id, viewed_at)');
        DB::statement('CREATE INDEX page_views_article_id_viewed_at_index ON page_views (article_id, viewed_at)');

        Schema::create('article_daily_stats', function (Blueprint $table) {
            $table->foreignUuid('article_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('avg_duration_ms')->default(0);

            $table->primary(['article_id', 'day']);
            $table->index(['blog_id', 'day']);
            $table->index(['author_profile_id', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_daily_stats');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('comments');
    }
};
