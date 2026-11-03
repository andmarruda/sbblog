<?php

namespace App\Support;

use App\Models\ArticleRevision;
use App\Publishing\Body\TiptapDocument;

/**
 * A readable diff between two revisions: fields side by side and the body compared block by
 * block (one line of plain text per block).
 */
class RevisionDiff
{
    /**
     * @return array{title: array{from: string, to: string}, description: array{from: string|null, to: string|null}, cover_changed: bool, body: list<array{op: 'equal'|'insert'|'delete', text: string}>}
     */
    public static function between(?ArticleRevision $from, ArticleRevision $to): array
    {
        return [
            'title' => ['from' => $from->title ?? '', 'to' => $to->title],
            'description' => ['from' => $from?->description, 'to' => $to->description],
            'cover_changed' => $from?->cover_media_id !== $to->cover_media_id,
            'body' => self::lines(self::blocks($from->body ?? []), self::blocks($to->body)),
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return list<string>
     */
    public static function blocks(array $document): array
    {
        $lines = [];

        foreach (is_array($document['content'] ?? null) ? $document['content'] : [] as $block) {
            if (! is_array($block)) {
                continue;
            }

            $text = match ($block['type'] ?? null) {
                'image' => '['.__('Image').'] '.($block['attrs']['alt'] ?? ''),
                'embed' => '['.__('Video').'] '.($block['attrs']['provider'] ?? '').' '.($block['attrs']['videoId'] ?? ''),
                'horizontalRule' => '———',
                default => TiptapDocument::plainText($block),
            };

            $prefix = ($block['type'] ?? null) === 'heading' ? str_repeat('#', (int) ($block['attrs']['level'] ?? 2)).' ' : '';
            $lines[] = $prefix.$text;
        }

        return $lines;
    }

    /**
     * Longest-common-subsequence line diff.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{op: 'equal'|'insert'|'delete', text: string}>
     */
    public static function lines(array $old, array $new): array
    {
        $n = count($old);
        $m = count($new);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $old[$i] === $new[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $ops = [];

        for ($i = 0, $j = 0; $i < $n || $j < $m;) {
            if ($i < $n && $j < $m && $old[$i] === $new[$j]) {
                $ops[] = ['op' => 'equal', 'text' => $old[$i]];
                $i++;
                $j++;
            } elseif ($j < $m && ($i >= $n || $lcs[$i][$j + 1] >= $lcs[$i + 1][$j])) {
                $ops[] = ['op' => 'insert', 'text' => $new[$j]];
                $j++;
            } else {
                $ops[] = ['op' => 'delete', 'text' => $old[$i]];
                $i++;
            }
        }

        return $ops;
    }
}
