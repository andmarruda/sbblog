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
        Schema::create('apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_first_party')->default(false);
            $table->string('client_id', 64)->unique();
            $table->string('client_secret_hash');
            $table->jsonb('redirect_uris')->default('[]');
            $table->jsonb('requested_scopes')->default('[]');
            $table->timestampsTz();
        });

        Schema::create('app_installations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('app_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->jsonb('granted_scopes')->default('[]');
            $table->foreignUuid('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index(['blog_id', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_installations');
        Schema::dropIfExists('apps');
    }
};
