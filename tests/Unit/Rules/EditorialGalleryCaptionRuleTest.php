<?php

namespace Tests\Unit\Rules;

use App\Rules\EditorialGalleryCaptionRule;
use Tests\TestCase;
use Validator;

final class EditorialGalleryCaptionRuleTest extends TestCase
{
    public function test_accepts_plain_text(): void
    {
        $v = Validator::make(['c' => 'Онлайн-трибуна «адвоката»'], ['c' => [new EditorialGalleryCaptionRule]]);
        $this->assertTrue($v->passes());
    }

    public function test_rejects_html_entities(): void
    {
        $v = Validator::make(['c' => 'Проект &quot;Онлайн&quot;'], ['c' => [new EditorialGalleryCaptionRule]]);
        $this->assertFalse($v->passes());
    }
}
