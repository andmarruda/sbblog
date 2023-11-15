<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->string('password')->change();
            $table->dropTimestamps();
        });

        Schema::table('users', function(Blueprint $table) {
            $table->timestampsTz();
        });

        if(User::where('email', 'admin@admin.com')->count() == 0)
        {
            Artisan::call('db:seed', [
                '--class' => 'UserSeeder',
                '--force' => true
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->string('password', 32)->change();
            $table->dropTimestampsTz();
        });

        Schema::table('users', function(Blueprint $table) {
            $table->timestamps();
        });
    }
};
