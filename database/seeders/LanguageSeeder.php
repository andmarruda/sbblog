<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Seed the supported languages.
     */
    public function run(): void
    {
        $languages = [
            'pt-BR' => 'Português (Brasil)',
            'en-US' => 'English (US)',
            'es-ES' => 'Español',
        ];

        foreach ($languages as $code => $label) {
            Language::query()->updateOrCreate(['code' => $code], ['label' => $label]);
        }
    }
}
