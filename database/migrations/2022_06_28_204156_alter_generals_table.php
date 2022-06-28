<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //adding column to google ads script
        Schema::table('generals', function (Blueprint $table) {
            $table->string('google_ads_script')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //remove column google ads script
        Schema::table('generals', function (Blueprint $table) {
            $table->dropColumn('google_ads_script');
        });
    }
};
