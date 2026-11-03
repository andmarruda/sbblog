<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which v2 row each v1 row became, so `sbblog:import-v1` can run again without duplicates
     * and `sbblog:verify-v1` can compare both sides.
     */
    public function up(): void
    {
        Schema::create('v1_import_map', function (Blueprint $table) {
            $table->foreignUuid('blog_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->unsignedBigInteger('source_id');
            $table->uuid('target_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['blog_id', 'source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v1_import_map');
    }
};
