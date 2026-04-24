<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\FaqAnswerForPublicView;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FaqAnswerForPublicViewTest extends TestCase
{
    #[TestWith([false])]
    #[TestWith([true])]
    public function test_plain_text_is_escaped_not_marked_as_html(bool $withNewline): void
    {
        $raw = $withNewline ? "строка\nс переносом" : 'просто текст <не тег>';
        $o = FaqAnswerForPublicView::fromStoredAnswer($raw);
        $this->assertFalse($o['is_html']);
        $this->assertStringNotContainsString('<p>', (string) $o['body']);
        $this->assertStringNotContainsString('<не тег>', (string) $o['body']);
    }

    public function test_html_paragraphs_are_sanitized_and_preserved(): void
    {
        $o = FaqAnswerForPublicView::fromStoredAnswer('<p>О<p>два</p> — нет</p><script>alert(1)</script>');
        $this->assertTrue($o['is_html']);
        $this->assertStringContainsString('<p>', (string) $o['body']);
        $this->assertStringNotContainsString('script', (string) $o['body']);
    }

    public function test_allowed_inline_tags_preserved_in_html_mode(): void
    {
        $o = FaqAnswerForPublicView::fromStoredAnswer(
            '<p>Текст <strong>важно</strong> и <a href="https://example.com/path">ссылка</a>.</p>'
        );
        $this->assertTrue($o['is_html']);
        $this->assertStringContainsString('<strong>', (string) $o['body']);
        $this->assertStringContainsString('href="https://example.com/path"', (string) $o['body']);
    }

    public function test_dangerous_attributes_and_iframe_stripped(): void
    {
        $o = FaqAnswerForPublicView::fromStoredAnswer(
            '<p onmouseover="x()">A</p><img src="y" onerror="bad()"><iframe src="https://evil"></iframe><p>ok</p>'
        );
        $this->assertTrue($o['is_html']);
        $b = (string) $o['body'];
        $this->assertStringNotContainsString('onmouseover', $b);
        $this->assertStringNotContainsString('onerror', $b);
        $this->assertStringNotContainsString('iframe', $b);
        $this->assertStringContainsString('ok', $b);
    }

    public function test_plain_text_preserves_newlines_in_body_for_nl2br(): void
    {
        $o = FaqAnswerForPublicView::fromStoredAnswer("Первый\n\nВторой");
        $this->assertFalse($o['is_html']);
        $this->assertStringContainsString("\n", (string) $o['body']);
    }
}
