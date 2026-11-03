<?php

namespace Tests\Feature\Migration;

use App\Migration\V1\QuillToTiptap;
use App\Publishing\SchemaValidator;
use Tests\TestCase;

class QuillToTiptapTest extends TestCase
{
    private const MEDIA = '0195d1c2-0000-7000-8000-000000000001';

    /**
     * @return array<string, mixed>
     */
    private function convert(string $html, ?QuillToTiptap &$converter = null): array
    {
        $converter = new QuillToTiptap(fn (string $src) => str_starts_with($src, 'data:') ? self::MEDIA : null);
        $doc = $converter->convert($html);

        $this->assertSame([], app(SchemaValidator::class)->errors($doc, 'body.schema.json'), json_encode($doc) ?: '');

        return $doc;
    }

    public function test_quill_output_fits_the_body_schema(): void
    {
        $doc = $this->convert(
            '<h1>Title</h1><p class="ql-align-center">Hi <strong>bold <em>both</em></strong> <a href="https://x.com" target="_blank">link</a><br></p>'
            .'<p>before <img src="data:image/png;base64,AAA" alt="pic"> after</p><ol><li data-list="bullet">b</li><li data-list="ordered">o</li></ol>'
            .'<pre class="ql-syntax">a &lt; b'."\n".'</pre><blockquote>quote <span style="color: red">red</span></blockquote>'
            .'<iframe class="ql-video" src="https://player.vimeo.com/video/123456"></iframe><h6>small</h6><h2><br></h2>',
        );

        $this->assertSame(['heading', 'paragraph', 'paragraph', 'image', 'paragraph', 'bulletList', 'orderedList', 'codeBlock', 'blockquote', 'embed', 'heading'], array_column($doc['content'], 'type'));
        $this->assertSame(['level' => 2], $doc['content'][0]['attrs']);
        $this->assertSame('center', $doc['content'][1]['attrs']['textAlign']);
        $this->assertSame(['bold', 'italic'], array_column($doc['content'][1]['content'][2]['marks'], 'type'));
        $this->assertSame(self::MEDIA, $doc['content'][3]['attrs']['mediaId']);
        $this->assertSame('a < b', $doc['content'][7]['content'][0]['text']);
        $this->assertSame(['provider' => 'vimeo', 'videoId' => '123456'], $doc['content'][9]['attrs']);
        $this->assertSame(4, $doc['content'][10]['attrs']['level']);
    }

    public function test_indented_items_become_nested_lists(): void
    {
        $doc = $this->convert('<ul><li>one</li><li class="ql-indent-1">a</li><li class="ql-indent-2">deep</li><li class="ql-indent-1">b</li><li>two</li></ul>');

        $list = $doc['content'][0];
        $this->assertCount(2, $list['content']);
        $nested = $list['content'][0]['content'][1];
        $this->assertSame('bulletList', $nested['type']);
        $this->assertCount(2, $nested['content']);
        $this->assertSame('bulletList', $nested['content'][0]['content'][1]['type']);
    }

    public function test_unsafe_or_unknown_content_is_dropped_with_warnings(): void
    {
        $doc = $this->convert(
            '<p><a href="javascript:alert(1)">click</a> <img src="https://cdn.example.com/x.png"></p><script>alert(1)</script>'
            .'<iframe src="https://evil.example.com"></iframe><p><span class="ql-formula" data-value="e=mc^2"></span></p>',
            $converter,
        );

        $this->assertStringNotContainsString('javascript', (string) json_encode($doc));
        $this->assertStringNotContainsString('alert', (string) json_encode($doc));
        $this->assertStringNotContainsString('evil', (string) json_encode($doc));
        $this->assertSame('e=mc^2', $doc['content'][count($doc['content']) - 1]['content'][0]['text']);
        $this->assertCount(4, $converter?->warnings() ?? []);
    }

    public function test_empty_html_gives_an_empty_paragraph(): void
    {
        $this->assertSame(['type' => 'doc', 'content' => [['type' => 'paragraph']]], $this->convert(''));
    }
}
