<?php

namespace App\Publishing\Body;

/**
 * Renders a validated Tiptap document to HTML. Everything is escaped; only the allow-listed
 * nodes and marks produce tags. Used for console previews and in event payloads, so frontends
 * that don't want to render JSON can use the HTML as is.
 */
class HtmlRenderer
{
    /**
     * @param  array<string, array{url: string, width: int|null, height: int|null, alt: string|null}>  $media
     *                                                                                                         Media by ID, for image nodes.
     */
    public function __construct(private readonly array $media = []) {}

    /**
     * @param  array<string, mixed>  $document
     */
    public function render(array $document): string
    {
        return $this->children($document);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function node(array $node): string
    {
        $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

        return match ($node['type'] ?? null) {
            'text' => $this->text($node),
            'hardBreak' => '<br>',
            'paragraph' => '<p'.$this->align($attrs).'>'.$this->children($node).'</p>',
            'heading' => $this->heading($node, $attrs),
            'bulletList' => '<ul>'.$this->children($node).'</ul>',
            'orderedList' => $this->orderedList($node, $attrs),
            'listItem' => '<li>'.$this->children($node).'</li>',
            'blockquote' => '<blockquote>'.$this->children($node).'</blockquote>',
            'codeBlock' => $this->codeBlock($node, $attrs),
            'horizontalRule' => '<hr>',
            'image' => $this->image($attrs),
            'embed' => $this->embed($attrs),
            'table' => '<table><tbody>'.$this->children($node).'</tbody></table>',
            'tableRow' => '<tr>'.$this->children($node).'</tr>',
            'tableHeader' => $this->cell('th', $node, $attrs),
            'tableCell' => $this->cell('td', $node, $attrs),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function children(array $node): string
    {
        $html = '';

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            $html .= is_array($child) ? $this->node($child) : '';
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function text(array $node): string
    {
        $html = $this->escape((string) ($node['text'] ?? ''));

        foreach (is_array($node['marks'] ?? null) ? $node['marks'] : [] as $mark) {
            if (! is_array($mark)) {
                continue;
            }

            $html = match ($mark['type'] ?? null) {
                'bold' => "<strong>{$html}</strong>",
                'italic' => "<em>{$html}</em>",
                'underline' => "<u>{$html}</u>",
                'strike' => "<s>{$html}</s>",
                'code' => "<code>{$html}</code>",
                'subscript' => "<sub>{$html}</sub>",
                'superscript' => "<sup>{$html}</sup>",
                'link' => $this->link($html, is_array($mark['attrs'] ?? null) ? $mark['attrs'] : []),
                default => $html,
            };
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function link(string $html, array $attrs): string
    {
        $href = (string) ($attrs['href'] ?? '');

        // The schema already restricts schemes; this is a second line of defence.
        // "//host" and "/\host" are other sites in disguise, not relative links.
        if (! preg_match('#^(https?://|mailto:|/(?![/\\\\])|\#)#i', $href)) {
            return $html;
        }

        $target = ($attrs['target'] ?? null) === '_blank' ? ' target="_blank" rel="noopener noreferrer nofollow"' : '';

        return '<a href="'.$this->escape($href).'"'.$target.'>'.$html.'</a>';
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $attrs
     */
    private function heading(array $node, array $attrs): string
    {
        $level = in_array($attrs['level'] ?? null, [2, 3, 4], true) ? $attrs['level'] : 2;

        return "<h{$level}".$this->align($attrs).'>'.$this->children($node)."</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $attrs
     */
    private function orderedList(array $node, array $attrs): string
    {
        $start = is_int($attrs['start'] ?? null) && $attrs['start'] > 1 ? ' start="'.$attrs['start'].'"' : '';

        return "<ol{$start}>".$this->children($node).'</ol>';
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $attrs
     */
    private function codeBlock(array $node, array $attrs): string
    {
        $language = is_string($attrs['language'] ?? null) && preg_match('/^[a-z0-9+#-]{1,32}$/', $attrs['language'])
            ? ' class="language-'.$this->escape($attrs['language']).'"'
            : '';

        $code = '';

        foreach (is_array($node['content'] ?? null) ? $node['content'] : [] as $child) {
            $code .= is_array($child) ? $this->escape((string) ($child['text'] ?? '')) : '';
        }

        return "<pre><code{$language}>{$code}</code></pre>";
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function image(array $attrs): string
    {
        $media = $this->media[(string) ($attrs['mediaId'] ?? '')] ?? null;

        if ($media === null) {
            return '';
        }

        $alt = $this->escape((string) ($attrs['alt'] ?? $media['alt'] ?? ''));
        $size = ($media['width'] ? ' width="'.$media['width'].'"' : '').($media['height'] ? ' height="'.$media['height'].'"' : '');
        $img = '<img src="'.$this->escape($media['url']).'" alt="'.$alt.'"'.$size.' loading="lazy">';
        $caption = is_string($attrs['caption'] ?? null) && $attrs['caption'] !== ''
            ? '<figcaption>'.$this->escape($attrs['caption']).'</figcaption>'
            : '';

        return "<figure>{$img}{$caption}</figure>";
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function embed(array $attrs): string
    {
        $id = (string) ($attrs['videoId'] ?? '');

        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
            return '';
        }

        $src = match ($attrs['provider'] ?? null) {
            'youtube' => "https://www.youtube-nocookie.com/embed/{$id}",
            'vimeo' => "https://player.vimeo.com/video/{$id}",
            default => null,
        };

        return $src === null ? '' : '<div class="embed"><iframe src="'.$src.'" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $attrs
     */
    private function cell(string $tag, array $node, array $attrs): string
    {
        $span = '';

        foreach (['colspan', 'rowspan'] as $key) {
            if (is_int($attrs[$key] ?? null) && $attrs[$key] > 1) {
                $span .= " {$key}=\"{$attrs[$key]}\"";
            }
        }

        return "<{$tag}{$span}>".$this->children($node)."</{$tag}>";
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function align(array $attrs): string
    {
        $align = $attrs['textAlign'] ?? null;

        return in_array($align, ['center', 'right', 'justify'], true) ? ' style="text-align: '.$align.'"' : '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
