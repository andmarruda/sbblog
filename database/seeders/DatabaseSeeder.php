<?php

namespace Database\Seeders;

use App\Models\SocialNetwork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(GeneralSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(SocialNetworkSeeder::class);
        $this->call(CommentConfigSeeder::class);
    }
}
