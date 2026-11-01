<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Seed the free default theme.
     */
    public function run(): void
    {
        Theme::query()->updateOrCreate(['slug' => 'default'], [
            'name' => 'Default',
            'package_name' => '@sbblog/theme-default',
            'latest_version' => '1.0.0',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'USD',
        ]);
    }
}
