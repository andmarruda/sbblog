<?php

namespace App\Actions\Themes;

use App\Models\Blog;
use App\Models\BlogTheme;
use App\Models\Theme;
use App\Publishing\EventRecorder;
use App\Publishing\Events\ThemeUpdated;
use App\Support\AuditLogger;
use App\Themes\ThemeConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Themes installed on a blog. At most one is active (also a partial unique index); every change
 * a reader can see is sent as `theme.updated`.
 */
class BlogThemes
{
    public function __construct(
        private readonly ThemeConfig $config,
        private readonly EventRecorder $events,
        private readonly AuditLogger $audit,
    ) {}

    public function install(Blog $blog, Theme $theme): BlogTheme
    {
        if ($theme->is_premium) {
            throw ValidationException::withMessages(['theme_id' => __('Premium themes are not available yet.')]);
        }

        return DB::transaction(function () use ($blog, $theme) {
            $existing = $blog->themes()->whereBelongsTo($theme)->first();

            if ($existing !== null) {
                return $existing;
            }

            $installed = $blog->themes()->create([
                'theme_id' => $theme->id,
                'version' => $theme->latest_version,
                'config' => $this->config->defaults($theme),
                'is_active' => false,
            ]);

            $this->audit->record('theme.installed', $installed, ['theme' => $theme->slug], blog: $blog);

            return $installed;
        });
    }

    public function activate(Blog $blog, BlogTheme $installed): void
    {
        DB::transaction(function () use ($blog, $installed) {
            Blog::query()->whereKey($blog->id)->lockForUpdate()->first();

            $blog->themes()->whereKeyNot($installed->id)->where('is_active', true)->update(['is_active' => false]);
            $installed->update([
                'is_active' => true,
                'config' => $this->config->migrate($installed->theme, (array) $installed->config),
            ]);

            $this->publish($blog, $installed);
            $this->audit->record('theme.activated', $installed, ['theme' => $installed->theme->slug], blog: $blog);
        });
    }

    /**
     * @param  array<array-key, mixed>  $input
     */
    public function configure(Blog $blog, BlogTheme $installed, array $input): void
    {
        $config = $this->config->validate($installed->theme, $input);

        DB::transaction(function () use ($blog, $installed, $config) {
            $installed->update(['config' => $config]);

            if ($installed->is_active) {
                $this->publish($blog, $installed);
            }

            $this->audit->record('theme.configured', $installed, ['theme' => $installed->theme->slug], blog: $blog);
        });
    }

    /**
     * Move to the theme's latest version, keeping the settings that still fit.
     */
    public function upgrade(Blog $blog, BlogTheme $installed): void
    {
        $theme = $installed->theme;

        if ($installed->version === $theme->latest_version) {
            return;
        }

        DB::transaction(function () use ($blog, $installed, $theme) {
            $from = $installed->version;
            $installed->update([
                'version' => $theme->latest_version,
                'config' => $this->config->migrate($theme, (array) $installed->config),
            ]);

            if ($installed->is_active) {
                $this->publish($blog, $installed);
            }

            $this->audit->record('theme.upgraded', $installed, ['theme' => $theme->slug, 'from' => $from, 'to' => $theme->latest_version], blog: $blog);
        });
    }

    public function uninstall(Blog $blog, BlogTheme $installed): void
    {
        if ($installed->is_active) {
            throw ValidationException::withMessages(['theme' => __('Activate another theme before removing this one.')]);
        }

        DB::transaction(function () use ($blog, $installed) {
            $installed->delete();
            $this->audit->record('theme.removed', null, ['theme' => $installed->theme->slug], blog: $blog);
        });
    }

    private function publish(Blog $blog, BlogTheme $installed): void
    {
        $this->events->record($blog, new ThemeUpdated($blog->id, $installed));
    }
}
