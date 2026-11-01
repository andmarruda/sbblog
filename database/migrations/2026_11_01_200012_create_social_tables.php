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
        Schema::create('social_networks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 32)->unique();
            $table->string('name');
            $table->string('icon')->nullable();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('social_network_id')->constrained()->restrictOnDelete();
            $table->string('url');
            $table->timestampsTz();

            $table->unique(['blog_id', 'author_profile_id', 'social_network_id'])->nullsNotDistinct();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('social_networks');
    }
};
