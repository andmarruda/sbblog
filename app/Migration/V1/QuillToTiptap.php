<?php

namespace App\Migration\V1;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Converts the HTML that v1's Quill editor saved into a Tiptap document that fits
 * resources/schemas/body.schema.json. Colors, fonts, formulas and other unsupported formatting
 * become plain text; images are resolved to media IDs by a callback, and anything that can't be
 * kept is reported as a warning.
 */
class QuillToTiptap
{
    private const LINK = '#^(https?://|mailto:|/(?![/\\\\])|\#)#i';

    /** @var list<string> */
    private array $warnings = [];

    /** @var callable(string): ?string */
    private $resolveImage;

    /**
     * @param  callable(string): ?string  $resolveImage  Turns an <img src> into a media ID, or null to drop it.
     */
    public function __construct(callable $resolveImage)
    {
        $this->resolveImage = $resolveImage;
    }

    /**
     * @return array{type: 'doc', content: list<array<string, mixed>>}
     */
    public function convert(string $html): array
    {
        $this->warnings = [];

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);
        $blocks = $body === null ? [] : $this->blocks($body);

        return ['type' => 'doc', 'content' => $blocks === [] ? [['type' => 'paragraph']] : $blocks];
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return array_values(array_unique($this->warnings));
    }

    /**
     * Block nodes for the children of $parent. Loose inline content is wrapped in paragraphs.
     *
     * @return list<array<string, mixed>>
     */
    private function blocks(DOMNode $parent): array
    {
        $blocks = [];
        $inline = [];

        $flush = function () use (&$blocks, &$inline) {
            if ($this->hasText($inline)) {
                $blocks[] = ['type' => 'paragraph', 'content' => $inline];
            }
            $inline = [];
        };

        $children = iterator_to_array($parent->childNodes);

        for ($i = 0; $i < count($children); $i++) {
            $node = $children[$i];

            if (! $node instanceof DOMElement) {
                array_push($inline, ...$this->inline($node, []));

                continue;
            }

            $tag = strtolower($node->tagName);

            if ($tag === 'ul' || $tag === 'ol') {
                $flush();
                array_push($blocks, ...$this->lists($node));
            } elseif (in_array($tag, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'img', 'iframe', 'hr', 'div', 'table'], true)) {
                $flush();
                array_push($blocks, ...$this->block($node, $tag));
            } else {
                array_push($inline, ...$this->inline($node, []));
            }
        }

        $flush();

        return $blocks;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function block(DOMElement $node, string $tag): array
    {
        return match ($tag) {
            'p' => $this->paragraph($node, 'paragraph'),
            'h1', 'h2' => $this->paragraph($node, 'heading', 2),
            'h3' => $this->paragraph($node, 'heading', 3),
            'h4', 'h5', 'h6' => $this->paragraph($node, 'heading', 4),
            'blockquote' => $this->blockquote($node),
            'pre' => $this->codeBlock($node),
            'img' => array_filter([$this->image($node)]),
            'iframe' => array_filter([$this->embed($node)]),
            'hr' => [['type' => 'horizontalRule']],
            default => $this->blocks($node),
        };
    }

    /**
     * A paragraph or heading. Images and videos inside it (Quill puts them in <p>) are moved out
     * as blocks of their own, in order.
     *
     * @return list<array<string, mixed>>
     */
    private function paragraph(DOMElement $node, string $type, ?int $level = null): array
    {
        $out = [];
        $inline = [];
        $attrs = array_filter(['level' => $level, 'textAlign' => $this->align($node)], fn ($value) => $value !== null);

        $flush = function (bool $keepEmpty = false) use (&$out, &$inline, $type, $attrs) {
            if ($this->hasText($inline) || ($keepEmpty && $type === 'paragraph')) {
                $out[] = array_filter(['type' => $type, 'attrs' => $attrs ?: null, 'content' => $this->hasText($inline) ? $this->trimBreaks($inline) : null]);
            }
            $inline = [];
        };

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['img', 'iframe'], true)) {
                $flush();
                $media = strtolower($child->tagName) === 'img' ? $this->image($child) : $this->embed($child);
                if ($media !== null) {
                    $out[] = $media;
                }

                continue;
            }

            array_push($inline, ...$this->inline($child, []));
        }

        $flush(keepEmpty: $out === []);

        // Headings need text; an empty one becomes nothing.
        return array_values(array_filter($out, fn (array $block) => $block['type'] !== 'heading' || isset($block['content'])));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blockquote(DOMElement $node): array
    {
        $content = $this->blocks($node);

        return $content === [] ? [] : [['type' => 'blockquote', 'content' => $content]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function codeBlock(DOMElement $node): array
    {
        $text = rtrim(str_replace("\r", '', $node->textContent), "\n");

        return [array_filter(['type' => 'codeBlock', 'content' => $text === '' ? null : [['type' => 'text', 'text' => $text]]])];
    }

    /**
     * Quill 1 writes nested lists as flat <li class="ql-indent-N">; Quill 2 marks the kind with
     * data-list on each item. Both become properly nested bulletList/orderedList nodes.
     *
     * @return list<array<string, mixed>>
     */
    private function lists(DOMElement $list): array
    {
        $items = [];

        foreach ($list->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                $kind = match ($child->getAttribute('data-list')) {
                    'bullet' => 'bulletList',
                    'ordered' => 'orderedList',
                    default => strtolower($list->tagName) === 'ol' ? 'orderedList' : 'bulletList',
                };
                preg_match('/ql-indent-(\d+)/', $child->getAttribute('class'), $match);
                $items[] = ['kind' => $kind, 'depth' => (int) ($match[1] ?? 0), 'node' => $child];
            } elseif ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true) && $items !== []) {
                // HTML-style nesting: a list directly inside a list.
                foreach ($this->lists($child) as $nested) {
                    $items[] = ['kind' => 'nested', 'depth' => 0, 'node' => $nested];
                }
            }
        }

        return $this->buildLists($items, 0);
    }

    /**
     * @param  list<array{kind: string, depth: int, node: DOMElement|array<string, mixed>}>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLists(array $items, int $depth): array
    {
        // Items in order, as two parallel lists; kind '' holds a list with no item to nest under.
        /** @var list<string> $kinds */
        $kinds = [];
        /** @var list<list<array<string, mixed>>> $contents */
        $contents = [];

        for ($i = 0; $i < count($items); $i++) {
            $item = $items[$i];
            $node = $item['node'];
            $last = count($kinds) - 1;
            $hasParent = $last >= 0 && $kinds[$last] !== '';

            if (is_array($node)) {
                // An HTML-nested list belongs to the previous item.
                if ($hasParent) {
                    $contents[$last][] = $node;
                } else {
                    $kinds[] = '';
                    $contents[] = [$node];
                }

                continue;
            }

            if ($item['depth'] > $depth && $hasParent) {
                // Collect the deeper run and nest it in the previous item.
                $run = [];
                while ($i < count($items) && $items[$i]['depth'] > $depth) {
                    $run[] = $items[$i++];
                }
                $i--;

                foreach ($this->buildLists($run, $depth + 1) as $nested) {
                    $contents[$last][] = $nested;
                }

                continue;
            }

            $paragraphs = $this->blocks($node);
            $kinds[] = $item['kind'];
            $contents[] = $paragraphs === [] ? [['type' => 'paragraph']] : $paragraphs;
        }

        // Consecutive items of the same kind share one list.
        /** @var list<array{type: string, items: list<array<string, mixed>>}> $groups */
        $groups = [];
        $open = false;

        foreach ($kinds as $index => $kind) {
            if ($kind === '') {
                foreach ($contents[$index] as $list) {
                    $groups[] = ['type' => '', 'items' => [$list]];
                }
                $open = false;

                continue;
            }

            $listItem = ['type' => 'listItem', 'content' => $contents[$index]];
            $last = count($groups) - 1;

            if ($open && $groups[$last]['type'] === $kind) {
                $groups[$last]['items'][] = $listItem;
            } else {
                $groups[] = ['type' => $kind, 'items' => [$listItem]];
                $open = true;
            }
        }

        return array_map(
            fn (array $group) => $group['type'] === '' ? $group['items'][0] : ['type' => $group['type'], 'content' => $group['items']],
            $groups,
        );
    }

    /**
     * Text nodes with marks for $node and its descendants.
     *
     * @param  list<array<string, mixed>>  $marks
     * @return list<array<string, mixed>>
     */
    private function inline(DOMNode $node, array $marks): array
    {
        if ($node instanceof DOMText) {
            $text = str_replace(["\r", "\n", "\t"], ['', ' ', ' '], $node->data);

            return $text === '' ? [] : [array_filter(['type' => 'text', 'text' => $text, 'marks' => $marks ?: null])];
        }

        if (! $node instanceof DOMElement) {
            return [];
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['script', 'style', 'noscript', 'template', 'object', 'embed'], true)) {
            return [];
        }

        if ($tag === 'br') {
            return [['type' => 'hardBreak']];
        }

        if ($tag === 'img' || $tag === 'iframe') {
            // Only reachable inside lists or quotes, where blocks can't go: keep a note instead.
            $this->warnings[] = "An {$tag} inside a list or quote was dropped.";

            return [];
        }

        if ($tag === 'span' && str_contains($node->getAttribute('class'), 'ql-formula')) {
            $this->warnings[] = 'Formulas were kept as plain text.';

            return [['type' => 'text', 'text' => $node->getAttribute('data-value') ?: $node->textContent]];
        }

        $mark = match ($tag) {
            'strong', 'b' => ['type' => 'bold'],
            'em', 'i' => ['type' => 'italic'],
            'u' => ['type' => 'underline'],
            's', 'strike', 'del' => ['type' => 'strike'],
            'code' => ['type' => 'code'],
            'sub' => ['type' => 'subscript'],
            'sup' => ['type' => 'superscript'],
            'a' => $this->link($node),
            default => null,
        };

        if ($mark !== null && ! in_array($mark['type'], array_column($marks, 'type'), true)) {
            $marks[] = $mark;
        }

        $out = [];
        foreach ($node->childNodes as $child) {
            array_push($out, ...$this->inline($child, $marks));
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function link(DOMElement $node): ?array
    {
        $href = trim($node->getAttribute('href'));

        if ($href === '' || strlen($href) > 2048 || preg_match(self::LINK, $href) !== 1) {
            if ($href !== '') {
                $this->warnings[] = 'Links with unsupported addresses were turned into text.';
            }

            return null;
        }

        return ['type' => 'link', 'attrs' => array_filter(['href' => $href, 'target' => $node->getAttribute('target') === '_blank' ? '_blank' : null])];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function image(DOMElement $node): ?array
    {
        $src = trim($node->getAttribute('src'));
        $mediaId = $src === '' ? null : ($this->resolveImage)($src);

        if ($mediaId === null) {
            $this->warnings[] = 'Images that could not be found were dropped.';

            return null;
        }

        $alt = trim($node->getAttribute('alt'));

        return ['type' => 'image', 'attrs' => array_filter(['mediaId' => $mediaId, 'alt' => $alt === '' ? null : mb_substr($alt, 0, 500)])];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function embed(DOMElement $node): ?array
    {
        $src = $node->getAttribute('src');

        $video = match (true) {
            preg_match('#(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=)|youtu\.be/)([A-Za-z0-9_-]{1,64})#', $src, $m) === 1 => ['youtube', $m[1]],
            preg_match('#(?:player\.)?vimeo\.com/(?:video/)?(\d{1,20})#', $src, $m) === 1 => ['vimeo', $m[1]],
            default => null,
        };

        if ($video === null) {
            $this->warnings[] = 'Embedded frames other than YouTube or Vimeo were dropped.';

            return null;
        }

        return ['type' => 'embed', 'attrs' => ['provider' => $video[0], 'videoId' => $video[1]]];
    }

    private function align(DOMElement $node): ?string
    {
        return preg_match('/ql-align-(center|right|justify)/', $node->getAttribute('class'), $match) === 1 ? $match[1] : null;
    }

    /**
     * @param  list<array<string, mixed>>  $inline
     */
    private function hasText(array $inline): bool
    {
        foreach ($inline as $node) {
            if ($node['type'] === 'text' && trim((string) $node['text']) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Quill ends empty lines with <br>; drop breaks at the edges of a paragraph.
     *
     * @param  list<array<string, mixed>>  $inline
     * @return list<array<string, mixed>>
     */
    private function trimBreaks(array $inline): array
    {
        while ($inline !== [] && $inline[0]['type'] === 'hardBreak') {
            array_shift($inline);
        }
        while ($inline !== [] && $inline[count($inline) - 1]['type'] === 'hardBreak') {
            array_pop($inline);
        }

        return $inline;
    }
}
