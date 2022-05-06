<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\General;

class GeneralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        General::create([
            'brand_image' => 'default',
            'slogan' => 'Compartilhando conhecimento através dos Blogs',
            'section' => 'Tecnologia',
            'active' => true
        ]);
    }
}
