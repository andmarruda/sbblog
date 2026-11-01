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
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->string('type', 64);
            $table->string('aggregate_type', 64);
            $table->uuid('aggregate_id');
            $table->unsignedInteger('schema_version')->default(1);
            $table->jsonb('payload');
            $table->timestampTz('occurred_at')->useCurrent();

            $table->unique(['blog_id', 'sequence']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('event_transports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->string('driver', 16);
            $table->text('config')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('last_dispatched_sequence')->default(0);
            $table->timestampTz('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestampsTz();
        });

        Schema::create('frontends', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('base_url');
            $table->string('store_adapter', 16);
            $table->string('sync_token_hash', 128)->unique();
            $table->unsignedBigInteger('last_acked_sequence')->default(0);
            $table->timestampTz('last_sync_at')->nullable();
            $table->string('core_version', 32)->nullable();
            $table->timestampsTz();
        });

        Schema::create('snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('up_to_sequence');
            $table->string('storage_path');
            $table->unsignedBigInteger('size_bytes');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['blog_id', 'up_to_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots');
        Schema::dropIfExists('frontends');
        Schema::dropIfExists('event_transports');
        Schema::dropIfExists('outbox_events');
    }
};
