<?php

namespace App\Apps;

/**
 * What an app or a personal token may do through the API. Writes never publish: apps create
 * draft revisions and suggestions only (ERD §3).
 */
final class Scopes
{
    public const ALL = [
        'articles:read' => 'Read articles, drafts included',
        'articles:write' => 'Save draft revisions and suggest changes',
        'media:write' => 'Upload media',
        'taxonomy:read' => 'Read categories, tags and authors',
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::ALL);
    }
}
