<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Review;
use Tests\TestCase;

final class ReviewPublicCardExcerptTest extends TestCase
{
    public function test_legacy_text_used_for_excerpt_when_body_empty_and_fallback_on(): void
    {
        $review = new Review([
            'body' => null,
            'text' => str_repeat('слова ', 120),
            'text_long' => null,
            'text_short' => null,
        ]);

        $plain = $review->publicBodyPlain();
        $this->assertNotSame('', $plain);
        $card = $review->publicCardExcerpt(Review::PUBLIC_CARD_EXCERPT_MAX_CHARS);
        $this->assertTrue(mb_strlen($card) <= Review::PUBLIC_CARD_EXCERPT_MAX_CHARS + 1);
        $this->assertTrue($review->publicWantsReadMore());
    }

    public function test_long_legacy_text_requires_read_more_even_when_short_column_differs(): void
    {
        $full = 'начало '.str_repeat('x', 500);
        $review = new Review([
            'body' => null,
            'text_short' => 'только кратко',
            'text_long' => $full,
            'text' => null,
        ]);

        $this->assertTrue($review->publicWantsReadMore());
        $this->assertNotSame($review->publicBodyPlain(), trim((string) ($review->text_short ?? '')));
    }

    public function test_short_review_no_read_more_when_fits(): void
    {
        $review = new Review([
            'body' => null,
            'text_long' => 'Коротко.',
            'text_short' => 'Коротко.',
            'text' => null,
        ]);

        $this->assertFalse($review->publicWantsReadMore());
    }

    public function test_legacy_read_uses_text_when_long_and_short_empty(): void
    {
        $review = new Review([
            'body' => null,
            'text_long' => '',
            'text' => 'Полный старый отзыв в legacy text.',
            'text_short' => null,
        ]);

        $this->assertSame('Полный старый отзыв в legacy text.', $review->publicFullTextRaw());
        $this->assertStringStartsWith('Полный старый', $review->publicBodyPlain());
    }

    public function test_legacy_read_prefers_text_short_over_text_when_long_empty(): void
    {
        $review = new Review([
            'body' => null,
            'text_long' => '',
            'text' => 'Длинный legacy в колонке text',
            'text_short' => 'Краткий',
        ]);

        $this->assertSame('Краткий', $review->publicFullTextRaw());
    }

    public function test_body_takes_precedence_over_legacy_columns(): void
    {
        $review = new Review([
            'body' => 'Канон',
            'text_long' => 'Наследие',
        ]);

        $this->assertSame('Канон', $review->publicFullTextRaw());
    }

    public function test_public_source_label_maps_known_platforms_only(): void
    {
        $this->assertSame('Отзывы с карт', (new Review(['source' => 'maps_curated']))->publicSourceLabel());
        $this->assertSame('Яндекс Карты', (new Review(['source' => 'yandex']))->publicSourceLabel());
        $this->assertSame('2ГИС', (new Review(['source' => '2gis']))->publicSourceLabel());
    }

    public function test_public_source_label_hides_site_and_import(): void
    {
        $this->assertNull((new Review(['source' => 'site']))->publicSourceLabel());
        $this->assertNull((new Review(['source' => 'import']))->publicSourceLabel());
        $this->assertNull((new Review(['source' => 'custom_unknown']))->publicSourceLabel());
    }
}
