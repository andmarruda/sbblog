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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignUuid('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('two_factor_policy', 32)->default('off');
            $table->string('plan', 32)->default('community');
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('workspace_members', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('workspace_role', 32)->default('member');
            $table->timestampTz('joined_at')->useCurrent();

            $table->primary(['workspace_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
