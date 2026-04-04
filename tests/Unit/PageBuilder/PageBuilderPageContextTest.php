<?php

namespace Tests\Unit\PageBuilder;

use App\Models\Page;
use App\PageBuilder\PageBuilderPageContext;
use PHPUnit\Framework\TestCase;

class PageBuilderPageContextTest extends TestCase
{
    public function test_home_is_landing_mode(): void
    {
        $p = new Page(['slug' => 'home']);
        $ctx = PageBuilderPageContext::fromPage($p);
        $this->assertTrue($ctx->isHome);
        $this->assertSame('landing', $ctx->mode);
        $this->assertSame('Главная', $ctx->modeLabel);
    }

    public function test_content_page_relabels_hero_in_ui(): void
    {
        $p = new Page(['slug' => 'rules']);
        $ctx = PageBuilderPageContext::fromPage($p);
        $this->assertFalse($ctx->isHome);
        $this->assertSame('Баннер страницы', $ctx->typeLabelForUi('hero', 'Hero'));
        $this->assertSame('Структурированный текст', $ctx->typeLabelForUi('structured_text', 'Структурированный текст'));
    }

    public function test_home_keeps_registry_hero_label(): void
    {
        $p = new Page(['slug' => 'home']);
        $ctx = PageBuilderPageContext::fromPage($p);
        $this->assertSame('Hero', $ctx->typeLabelForUi('hero', 'Hero'));
    }
}
