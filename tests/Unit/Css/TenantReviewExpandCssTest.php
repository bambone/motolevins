<?php

declare(strict_types=1);

namespace Tests\Unit\Css;

use PHPUnit\Framework\TestCase;

final class TenantReviewExpandCssTest extends TestCase
{
    public function test_review_toggle_is_hidden_until_html_js_class(): void
    {
        $path = dirname(__DIR__, 3).'/resources/css/app.css';
        $this->assertFileExists($path);
        $css = (string) file_get_contents($path);
        $this->assertStringContainsString('[data-review-toggle] {', $css);
        $this->assertStringContainsString('html.js [data-review-toggle] {', $css);
        $this->assertStringContainsString('display: inline-flex', $css);
        $this->assertStringContainsString('Кнопка раскрытия только при JS', $css);
    }
}
