<?php

namespace App\Models;

use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<array-key, mixed>|null $config_schema Settings fields, see App\Themes\ThemeConfig.
 */
#[Fillable(['slug', 'name', 'description', 'preview_url', 'package_name', 'latest_version', 'config_schema', 'is_premium', 'price_cents', 'currency'])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'price_cents' => 'integer',
            'config_schema' => 'array',
        ];
    }

    /**
     * The theme's settings fields (see App\Themes\ThemeConfig).
     *
     * @return list<array<array-key, mixed>>
     */
    public function fields(): array
    {
        return array_values(array_filter($this->config_schema ?? [], is_array(...)));
    }

    /**
     * @return HasMany<ThemeLicense, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(ThemeLicense::class);
    }

    /**
     * @return HasMany<BlogTheme, $this>
     */
    public function blogThemes(): HasMany
    {
        return $this->hasMany(BlogTheme::class);
    }
}
