<?php

namespace App\Publishing\Events;

use App\Models\BlogTheme;

/**
 * The blog's active theme, its version or its settings changed. `theme` is null when the blog
 * has no active theme (frontends fall back to their default).
 */
final class ThemeUpdated extends PublishableEvent
{
    public function __construct(private readonly string $blogId, private readonly ?BlogTheme $theme) {}

    public static function type(): string
    {
        return 'theme.updated';
    }

    public function aggregateType(): string
    {
        return 'blog';
    }

    public function aggregateId(): string
    {
        return $this->blogId;
    }

    public function payload(): array
    {
        if ($this->theme === null) {
            return ['theme' => null];
        }

        $theme = $this->theme->theme;

        return ['theme' => [
            'slug' => $theme->slug,
            'package_name' => $theme->package_name,
            'version' => $this->theme->version,
            'config' => (object) ($this->theme->config ?? []),
        ]];
    }
}
