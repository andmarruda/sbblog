<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

class Slug
{
    /**
     * Lowercase ASCII words joined by dashes. Used for validation as well.
     */
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * A slug from $source that $taken() says is free, adding -2, -3… when needed.
     *
     * @param  Closure(string): bool  $taken
     * @param  list<string>  $reserved
     */
    public static function unique(string $source, Closure $taken, array $reserved = [], int $maxLength = 63): string
    {
        $base = Str::limit(Str::slug($source), $maxLength - 4, '') ?: 'item';
        $base = rtrim($base, '-');
        $slug = $base;

        for ($suffix = 2; in_array($slug, $reserved, true) || $taken($slug); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
