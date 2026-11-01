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
        Schema::create('themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('package_name');
            $table->string('latest_version', 32);
            $table->boolean('is_premium')->default(false);
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->timestampsTz();
        });

        Schema::create('theme_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('theme_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('license_key_hash', 128);
            $table->timestampTz('valid_until')->nullable();
            $table->timestampsTz();

            $table->index(['workspace_id', 'theme_id']);
        });

        Schema::create('blog_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('theme_id')->constrained()->restrictOnDelete();
            $table->string('version', 32);
            $table->jsonb('config')->default('{}');
            $table->boolean('is_active')->default(false);
            $table->timestampsTz();
        });

        DB::statement('CREATE UNIQUE INDEX blog_themes_one_active_per_blog ON blog_themes (blog_id) WHERE is_active');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_themes');
        Schema::dropIfExists('theme_licenses');
        Schema::dropIfExists('themes');
    }
};
