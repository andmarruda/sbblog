<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $disabled = Category::where('active', false)->get();
        foreach($disabled as $category)
            $category->delete();
        
        Schema::table('categories', function(Blueprint $table){
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
        Schema::table('categories', function(Blueprint $table){
            $table->boolean('active')->default('true');
        });

        $disabled = Category::onlyTrashed()->get();
        foreach($disabled as $category){
            $category->active = false;
            $category->save();
            $category->restore();
        }
    }
};
