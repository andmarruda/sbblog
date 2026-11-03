<?php

namespace Tests\Unit\Publishing;

use App\Publishing\Body\HtmlRenderer;
use App\Publishing\Body\TiptapDocument;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TiptapDocumentTest extends TestCase
{
    private const MEDIA_ID = '01a1a1a1-a1a1-7a1a-8a1a-a1a1a1a1a1a1';

    /**
     * @return array<string, mixed>
     */
    private function doc(array ...$blocks): array
    {
        return ['type' => 'doc', 'content' => $blocks];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraph(array ...$inline): array
    {
        return ['type' => 'paragraph', 'content' => $inline];
    }

    public function test_a_rich_document_is_valid(): void
    {
        TiptapDocument::validate($this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2, 'textAlign' => null], 'content' => [['type' => 'text', 'text' => 'Title']]],
            $this->paragraph(
                ['type' => 'text', 'text' => 'Hello '],
                ['type' => 'text', 'text' => 'world', 'marks' => [['type' => 'bold'], ['type' => 'link', 'attrs' => ['href' => 'https://example.com', 'target' => '_blank']]]],
                ['type' => 'hardBreak'],
            ),
            ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [$this->paragraph(['type' => 'text', 'text' => 'one'])]]]],
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php'], 'content' => [['type' => 'text', 'text' => 'echo 1;']]],
            ['type' => 'image', 'attrs' => ['mediaId' => self::MEDIA_ID, 'alt' => 'A cat', 'caption' => null]],
            ['type' => 'embed', 'attrs' => ['provider' => 'youtube', 'videoId' => 'dQw4w9WgXcQ']],
            ['type' => 'table', 'content' => [['type' => 'tableRow', 'content' => [
                ['type' => 'tableHeader', 'attrs' => ['colspan' => 1, 'rowspan' => 1, 'colwidth' => null], 'content' => [$this->paragraph(['type' => 'text', 'text' => 'A'])]],
            ]]]],
        ));

        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidDocuments(): array
    {
        return [
            'unknown node' => [['type' => 'doc', 'content' => [['type' => 'script', 'content' => []]]]],
            'raw html attribute' => [['type' => 'doc', 'content' => [['type' => 'paragraph', 'attrs' => ['html' => '<b>x</b>']]]]],
            'javascript link' => [['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
            ]]]]],
            'h1 heading' => [['type' => 'doc', 'content' => [['type' => 'heading', 'attrs' => ['level' => 1]]]]],
            'image by url' => [['type' => 'doc', 'content' => [['type' => 'image', 'attrs' => ['src' => 'https://evil.test/x.png']]]]],
            'unknown embed' => [['type' => 'doc', 'content' => [['type' => 'embed', 'attrs' => ['provider' => 'evil', 'videoId' => 'x']]]]],
            'embed id injection' => [['type' => 'doc', 'content' => [['type' => 'embed', 'attrs' => ['provider' => 'youtube', 'videoId' => 'x"><script>']]]]],
            'not a doc' => [['type' => 'paragraph']],
        ];
    }

    #[DataProvider('invalidDocuments')]
    public function test_unsupported_content_is_rejected(array $document): void
    {
        $this->expectException(ValidationException::class);

        TiptapDocument::validate($document);
    }

    public function test_oversized_documents_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        TiptapDocument::validate($this->doc($this->paragraph(['type' => 'text', 'text' => str_repeat('a', TiptapDocument::MAX_BYTES)])));
    }

    public function test_renderer_escapes_text_and_hardens_links(): void
    {
        $html = (new HtmlRenderer)->render($this->doc($this->paragraph(
            ['type' => 'text', 'text' => '<script>alert("x")</script> & '],
            ['type' => 'text', 'text' => 'site', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'https://example.com/?a=1&b="2"', 'target' => '_blank']]]],
        )));

        $this->assertSame(
            '<p>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; <a href="https://example.com/?a=1&amp;b=&quot;2&quot;" target="_blank" rel="noopener noreferrer nofollow">site</a></p>',
            $html,
        );
    }

    public function test_renderer_drops_links_with_unsafe_schemes(): void
    {
        $html = (new HtmlRenderer)->render($this->doc($this->paragraph(
            ['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
        )));

        $this->assertSame('<p>x</p>', $html);
    }

    public function test_renderer_resolves_images_and_embeds(): void
    {
        $renderer = new HtmlRenderer([self::MEDIA_ID => ['url' => 'https://cdn.test/a.webp', 'width' => 800, 'height' => 600, 'alt' => 'Stored alt']]);

        $html = $renderer->render($this->doc(
            ['type' => 'image', 'attrs' => ['mediaId' => self::MEDIA_ID, 'caption' => 'Cap']],
            ['type' => 'image', 'attrs' => ['mediaId' => '01a1a1a1-0000-7a1a-8a1a-a1a1a1a1a1a1']],
            ['type' => 'embed', 'attrs' => ['provider' => 'vimeo', 'videoId' => '123']],
        ));

        $this->assertStringContainsString('<figure><img src="https://cdn.test/a.webp" alt="Stored alt" width="800" height="600" loading="lazy"><figcaption>Cap</figcaption></figure>', $html);
        $this->assertSame(1, substr_count($html, '<img'), 'Unknown media renders nothing.');
        $this->assertStringContainsString('https://player.vimeo.com/video/123', $html);
    }

    public function test_plain_text_excerpt_and_media_ids(): void
    {
        $doc = $this->doc(
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Title']]],
            $this->paragraph(['type' => 'text', 'text' => 'First '], ['type' => 'text', 'text' => 'line']),
            ['type' => 'image', 'attrs' => ['mediaId' => self::MEDIA_ID]],
            ['type' => 'blockquote', 'content' => [['type' => 'image', 'attrs' => ['mediaId' => self::MEDIA_ID]]]],
        );

        $this->assertSame("Title\nFirst line", TiptapDocument::plainText($doc));
        $this->assertSame('Title First line', TiptapDocument::excerpt($doc));
        $this->assertSame([self::MEDIA_ID], TiptapDocument::mediaIds($doc));
    }
}
