<?php

namespace App\Publishing\Body;

use App\Publishing\SchemaValidator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Helpers for article bodies: Tiptap JSON documents (resources/schemas/body.schema.json).
 */
class TiptapDocument
{
    /** Serialized size limit, ~1 MB of JSON. */
    public const MAX_BYTES = 1_000_000;

    public const SCHEMA = 'body.schema.json';

    /**
     * @return array{type: 'doc', content: list<array<string, mixed>>}
     */
    public static function empty(): array
    {
        return ['type' => 'doc', 'content' => []];
    }

    /**
     * @throws ValidationException
     */
    public static function validate(mixed $document, string $attribute = 'body'): void
    {
        $json = json_encode($document);

        if ($json === false || strlen($json) > self::MAX_BYTES) {
            throw ValidationException::withMessages([$attribute => __('The article is too long.')]);
        }

        $errors = app(SchemaValidator::class)->errors($document, self::SCHEMA);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                $attribute => __('The article contains content that is not supported.'),
            ]);
        }
    }

    /**
     * Text only, block by block, for excerpts and search.
     *
     * @param  array<string, mixed>  $node
     */
    public static function plainText(array $node): string
    {
        if (($node['type'] ?? null) === 'text') {
            return (string) ($node['text'] ?? '');
        }

        if (($node['type'] ?? null) === 'hardBreak') {
            return "\n";
        }

        $children = array_map(
            fn (mixed $child) => is_array($child) ? self::plainText($child) : '',
            is_array($node['content'] ?? null) ? $node['content'] : [],
        );

        $isBlock = ! in_array($node['type'] ?? null, ['paragraph', 'heading', 'codeBlock'], true);

        return trim(implode($isBlock ? "\n" : '', $children));
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public static function excerpt(array $document, int $limit = 280): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', self::plainText($document)) ?? '', $limit);
    }

    /**
     * IDs of every media referenced by image nodes.
     *
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    public static function mediaIds(array $node): array
    {
        $ids = [];

        if (($node['type'] ?? null) === 'image' && is_string($node['attrs']['mediaId'] ?? null)) {
            $ids[] = $node['attrs']['mediaId'];
        }

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            if (is_array($child)) {
                $ids = [...$ids, ...self::mediaIds($child)];
            }
        }

        return array_values(array_unique($ids));
    }
}
