<?php

namespace Tests\Unit\Rules;

use App\Rules\EditorialGalleryMaterialSourceUrlRule;
use Tests\TestCase;
use Validator;

final class EditorialGalleryMaterialSourceUrlRuleTest extends TestCase
{
    public function test_accepts_https(): void
    {
        $v = Validator::make(['u' => 'https://example.com/a'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertTrue($v->passes());
    }

    public function test_accepts_http(): void
    {
        $v = Validator::make(['u' => 'http://example.com/article'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertTrue($v->passes());
    }

    public function test_accepts_empty(): void
    {
        $v = Validator::make(['u' => ''], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertTrue($v->passes());
    }

    public function test_rejects_relative_path(): void
    {
        $v = Validator::make(['u' => '/contacts'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_hash_only(): void
    {
        $v = Validator::make(['u' => '#section'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_protocol_relative(): void
    {
        $v = Validator::make(['u' => '//evil.com/x'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_triple_slash_host(): void
    {
        $v = Validator::make(['u' => '///evil.com'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_https_without_host(): void
    {
        $v = Validator::make(['u' => 'https://'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_http_triple_slash(): void
    {
        $v = Validator::make(['u' => 'http:///broken'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_mailto(): void
    {
        $v = Validator::make(['u' => 'mailto:a@b.c'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_tel(): void
    {
        $v = Validator::make(['u' => 'tel:+79001234567'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }

    public function test_rejects_javascript(): void
    {
        $v = Validator::make(['u' => 'javascript:alert(1)'], ['u' => [new EditorialGalleryMaterialSourceUrlRule]]);
        $this->assertFalse($v->passes());
    }
}
