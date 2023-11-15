<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Language::create([
                'lang_id' => 'en',
                'label' => 'English',
                'icon' => 'images/lang/en.png'
        ]);

        \App\Models\Language::create([
            'lang_id' => 'pt',
            'label' => 'Português',
            'icon' => 'images/lang/pt.png'
        ]);
    }
}
