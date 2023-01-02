<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CommentConfig;

class CommentConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CommentConfig::create([
            'language_id' => 1,
            'description' => 'Public',
            'is_public' => true
        ]);

        CommentConfig::create([
            'language_id' => 1,
            'description' => 'Need admin validation',
            'is_public' => false
        ]);

        CommentConfig::create([
            'language_id' => 2,
            'description' => 'Público',
            'is_public' => true
        ]);

        CommentConfig::create([
            'language_id' => 2,
            'description' => 'Validado pelo administrador',
            'is_public' => false
        ]);
    }
}
