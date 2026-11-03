<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalog details and the field list the console turns into the theme settings form.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('preview_url')->nullable()->after('description');
            $table->jsonb('config_schema')->default('[]')->after('latest_version');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['description', 'preview_url', 'config_schema']);
        });
    }
};
