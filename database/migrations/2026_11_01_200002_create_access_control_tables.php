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
        Schema::create('permissions', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->string('group', 32);
            $table->string('scope', 16);
            $table->boolean('requires_two_factor')->default(false);
            $table->string('description')->nullable();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('name');
            $table->boolean('is_system')->default(false);
            $table->boolean('requires_two_factor')->default(false);
            $table->timestampsTz();

            // Built-in roles have workspace_id = null and must still be unique by key.
            $table->unique(['workspace_id', 'key'])->nullsNotDistinct();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained()->cascadeOnDelete();
            $table->string('permission_key', 64);

            $table->primary(['role_id', 'permission_key']);
            $table->foreign('permission_key')->references('key')->on('permissions')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('blog_members', function (Blueprint $table) {
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['blog_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_members');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
