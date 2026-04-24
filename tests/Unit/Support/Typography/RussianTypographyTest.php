<?php

namespace Tests\Unit\Support\Typography;

use App\Support\Typography\RussianTypography;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RussianTypographyTest extends TestCase
{
    public static function headingsProvider(): array
    {
        $nbsp = "\u{00A0}";

        return [
            'preposition в before city' => [
                'Уверенное вождение в городе',
                'Уверенное вождение в'.$nbsp.'городе',
            ],
            'chain и в' => [
                'на парковке и в сложных условиях',
                'на'.$nbsp.'парковке и'.$nbsp.'в'.$nbsp.'сложных условиях',
            ],
            'по before word' => [
                'КМС по автоспорту',
                'КМС по'.$nbsp.'автоспорту',
            ],
            'em dash stays with previous and next word' => [
                'Формат работы — сначала созвон',
                'Формат работы'.$nbsp.'—'.$nbsp.'сначала созвон',
            ],
            'не tied to next word' => [
                'это не маркетинговый текст',
                'это не'.$nbsp.'маркетинговый текст',
            ],
            'em dash with без' => [
                'Конец — без продолжения',
                'Конец'.$nbsp.'—'.$nbsp.'без'.$nbsp.'продолжения',
            ],
            'без before guillemets' => [
                'тенанта — без «склейки» сторонних',
                'тенанта'.$nbsp.'—'.$nbsp.'без'.$nbsp.'«склейки» сторонних',
            ],
            'Moto hero: на with Чёрном' => [
                'Аренда мотоциклов на Чёрном море',
                'Аренда мотоциклов на'.$nbsp.'Чёрном море',
            ],
        ];
    }

    #[DataProvider('headingsProvider')]
    public function test_ties_prepositions(string $input, string $expected): void
    {
        $this->assertSame($expected, RussianTypography::tiePrepositionsToNextWord($input));
    }

    public function test_tie_prepositions_per_line_preserves_paragraph_breaks(): void
    {
        $nbsp = "\u{00A0}";
        $input = "Первый абзац в городе\n\nВторой по делу";
        $expected = 'Первый абзац в'.$nbsp.'городе'."\n\n".'Второй по'.$nbsp.'делу';
        $this->assertSame($expected, RussianTypography::tiePrepositionsPerLine($input));
    }

    public function test_wrap_phrase_handles_nbsp_between_words(): void
    {
        $nbsp = "\u{00A0}";
        $typo = 'Медиа и'.$nbsp.'бренд — логотип.';
        $expected = '<span class="font-medium text-slate-800">Медиа и'.$nbsp.'бренд</span> — логотип.';
        $this->assertSame($expected, RussianTypography::wrapPhrase($typo, 'Медиа и бренд'));
    }
}
