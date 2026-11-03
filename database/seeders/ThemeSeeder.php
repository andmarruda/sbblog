<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Settings fields of @sbblog/theme-default (see App\Themes\ThemeConfig).
     */
    public const DEFAULT_FIELDS = [
        ['key' => 'primary_color', 'type' => 'color', 'label' => 'Primary color', 'group' => 'Colors', 'default' => '#2563eb'],
        ['key' => 'accent_color', 'type' => 'color', 'label' => 'Accent color', 'group' => 'Colors', 'default' => '#f59e0b'],
        ['key' => 'color_scheme', 'type' => 'select', 'label' => 'Color scheme', 'group' => 'Colors', 'options' => ['system', 'light', 'dark'], 'default' => 'system'],
        ['key' => 'home_layout', 'type' => 'select', 'label' => 'Home layout', 'group' => 'Layout', 'options' => ['list', 'grid', 'magazine'], 'default' => 'list'],
        ['key' => 'font', 'type' => 'select', 'label' => 'Font', 'group' => 'Layout', 'options' => ['sans', 'serif', 'mono'], 'default' => 'sans'],
        ['key' => 'header_style', 'type' => 'select', 'label' => 'Header style', 'group' => 'Header', 'options' => ['simple', 'centered', 'banner'], 'default' => 'simple'],
        ['key' => 'show_search', 'type' => 'boolean', 'label' => 'Show search', 'group' => 'Header', 'default' => true],
        ['key' => 'show_author', 'type' => 'boolean', 'label' => 'Show author on articles', 'group' => 'Articles', 'default' => true],
        ['key' => 'show_reading_time', 'type' => 'boolean', 'label' => 'Show reading time', 'group' => 'Articles', 'default' => true],
        ['key' => 'footer_text', 'type' => 'text', 'label' => 'Footer text', 'group' => 'Footer', 'default' => ''],
    ];

    /**
     * Seed the free default theme.
     */
    public function run(): void
    {
        Theme::query()->updateOrCreate(['slug' => 'default'], [
            'name' => 'Default',
            'description' => 'A clean reading layout with a list or grid home page, light and dark modes.',
            'preview_url' => null,
            'config_schema' => self::DEFAULT_FIELDS,
            'package_name' => '@sbblog/theme-default',
            'latest_version' => '1.0.0',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'USD',
        ]);
    }
}
