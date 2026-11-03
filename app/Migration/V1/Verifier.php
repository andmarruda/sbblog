<?php

namespace App\Migration\V1;

use App\Models\ArticleRevision;
use App\Models\Blog;
use App\Publishing\Body\HtmlRenderer;
use App\Publishing\Body\TiptapDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

/**
 * Compares a v1 database with the blog it was imported into (console plan, M-02): row counts,
 * a checksum of every article's title and text, and a sample of rendered articles.
 */
class Verifier
{
    /** Articles whose text differs more than this are reported. */
    public const MIN_SIMILARITY = 95.0;

    public function __construct(private readonly HtmlRenderer $renderer) {}

    /**
     * @return array{counts: list<array{entity: string, v1: int, v2: int, ok: bool}>, mismatches: list<array{id: int, title: string, similarity: float, title_ok: bool}>, sample: list<array{id: int, title: string, v1_chars: int, v2_chars: int, html_chars: int, similarity: float}>}
     */
    public function run(string $connection, Blog $blog, int $sample = 5): array
    {
        $source = DB::connection($connection);
        $map = fn (string $entity) => DB::table('v1_import_map')->where('blog_id', $blog->id)->where('source', $entity);

        $tags = $source->table('article_tags')->pluck('tag')
            ->map(fn ($tag) => Str::slug(trim((string) $tag)))->filter()->unique()->count();

        $counts = [
            $this->count('users (active)', $source->table('users')->whereNull('deleted_at')->count(), $blog->members()->count()),
            $this->count('categories', $source->table('categories')->count(), $blog->categories()->withTrashed()->count()),
            $this->count('articles', $source->table('articles')->count(), $blog->articles()->withTrashed()->whereIn('id', $map('articles')->select('target_id'))->count()),
            $this->count('articles (live)', $source->table('articles')->whereNull('deleted_at')->count(), $blog->articles()->whereIn('id', $map('articles')->select('target_id'))->count()),
            $this->count('tags', $tags, $blog->tags()->count()),
            $this->count('comments', $source->table('article_comments')->whereIn('article_id', $source->table('articles')->select('id'))->count(), $map('comments')->count()),
        ];

        $mismatches = [];
        $checked = [];

        foreach ($map('articles')->get(['source_id', 'target_id']) as $link) {
            /** @var stdClass|null $row */
            $row = $source->table('articles')->where('id', $link->source_id)->first(['id', 'title', 'article']);
            $revision = ArticleRevision::query()->where('article_id', $link->target_id)->where('number', 1)->first();

            if ($row === null || $revision === null) {
                $mismatches[] = ['id' => (int) $link->source_id, 'title' => (string) ($row->title ?? '?'), 'similarity' => 0.0, 'title_ok' => false];

                continue;
            }

            $v1 = self::normalize(self::htmlText((string) $row->article));
            $v2 = self::normalize(TiptapDocument::plainText((array) $revision->body));
            $similarity = self::similarity($v1, $v2);
            $titleOk = hash_equals(sha1(trim((string) $row->title)), sha1($revision->title));

            $checked[] = ['row' => $row, 'revision' => $revision, 'v1' => $v1, 'v2' => $v2, 'similarity' => $similarity];

            if (! $titleOk || $similarity < self::MIN_SIMILARITY) {
                $mismatches[] = ['id' => (int) $row->id, 'title' => (string) $row->title, 'similarity' => $similarity, 'title_ok' => $titleOk];
            }
        }

        shuffle($checked);
        $spot = array_map(fn (array $item) => [
            'id' => (int) $item['row']->id,
            'title' => (string) $item['row']->title,
            'v1_chars' => mb_strlen($item['v1']),
            'v2_chars' => mb_strlen($item['v2']),
            'html_chars' => mb_strlen($this->renderer->render((array) $item['revision']->body)),
            'similarity' => $item['similarity'],
        ], array_slice($checked, 0, $sample));

        return ['counts' => $counts, 'mismatches' => $mismatches, 'sample' => $spot];
    }

    /**
     * @return array{entity: string, v1: int, v2: int, ok: bool}
     */
    private function count(string $entity, int $v1, int $v2): array
    {
        return ['entity' => $entity, 'v1' => $v1, 'v2' => $v2, 'ok' => $v2 >= $v1];
    }

    /**
     * The text of v1 HTML, with block elements separated like the v2 plain text.
     */
    public static function htmlText(string $html): string
    {
        $html = preg_replace('#<span[^>]*class="[^"]*ql-formula[^"]*"[^>]*data-value="([^"]*)"[^>]*>.*?</span>#is', '$1', $html) ?? $html;
        $html = preg_replace('#<(br|/p|/h[1-6]|/li|/blockquote|/pre)[^>]*>#i', ' ', $html) ?? $html;

        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
    }

    public static function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 100.0;
        }

        // similar_text is quadratic: compare long texts by their words instead.
        if (max(strlen($a), strlen($b)) > 5000) {
            $wordsA = array_count_values(explode(' ', $a));
            $wordsB = array_count_values(explode(' ', $b));
            $common = 0;
            foreach ($wordsA as $word => $count) {
                $common += min($count, $wordsB[$word] ?? 0);
            }

            return round(200 * $common / max(1, array_sum($wordsA) + array_sum($wordsB)), 2);
        }

        similar_text($a, $b, $percent);

        return round($percent, 2);
    }
}
