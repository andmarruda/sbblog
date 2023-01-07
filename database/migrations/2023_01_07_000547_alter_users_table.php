<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //remove active and add softdelete
        Schema::table('users', function(Blueprint $table){
            $table->softDeletesTz();
        });

        foreach(User::all() as $user){
            if(!$user->active)
                $user->delete();
        }

        Schema::table('users', function(Blueprint $table){
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //add active and drop softdelete
        Schema::table('users', function(Blueprint $table){
            $table->boolean('active')->default('true');
        });

        foreach(User::withTrashed()->all() as $user){
            if(!is_null($user->deleted_at)){
                $user->active = false;
                $user->save();
            }
        }

        Schema::table('users', function(Blueprint $table){
            $table->dropSoftDeletesTz();
        });
    }
};
