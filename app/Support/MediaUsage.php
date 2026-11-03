<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class MediaUsage
{
    /**
     * Where a media is still used: live or draft content of non-deleted articles, the blog brand
     * and author avatars. Older revisions don't count.
     *
     * @return array{articles: int, brand: bool, avatars: int}
     */
    public static function of(Media $media): array
    {
        $revisionIds = DB::table('articles')
            ->where('blog_id', $media->blog_id)
            ->whereNull('deleted_at')
            ->select('current_revision_id as id')
            ->union(DB::table('articles')->where('blog_id', $media->blog_id)->whereNull('deleted_at')->whereNotNull('published_revision_id')->select('published_revision_id as id'));

        $articles = DB::table('article_revisions')
            ->whereIn('id', $revisionIds)
            ->where(fn ($query) => $query
                ->where('cover_media_id', $media->id)
                ->orWhereRaw('jsonb_path_exists(body, \'$.** ? (@.mediaId == $id)\', jsonb_build_object(\'id\', ?::text))', [$media->id]))
            ->distinct()
            ->count('article_id');

        return [
            'articles' => $articles,
            'brand' => DB::table('blogs')->where('brand_media_id', $media->id)->exists(),
            'avatars' => DB::table('author_profiles')->where('avatar_media_id', $media->id)->count(),
        ];
    }

    /**
     * Revisions (any) whose cover or body uses the media.
     */
    public static function revisionsUsing(Media $media): Builder
    {
        return DB::table('article_revisions')
            ->join('articles', 'articles.id', '=', 'article_revisions.article_id')
            ->where('articles.blog_id', $media->blog_id)
            ->where(fn ($query) => $query
                ->where('article_revisions.cover_media_id', $media->id)
                ->orWhereRaw('jsonb_path_exists(article_revisions.body, \'$.** ? (@.mediaId == $id)\', jsonb_build_object(\'id\', ?::text))', [$media->id]))
            ->select('article_revisions.id');
    }

    public static function inUse(Media $media): bool
    {
        $usage = self::of($media);

        return $usage['articles'] > 0 || $usage['brand'] || $usage['avatars'] > 0;
    }
}
