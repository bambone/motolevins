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
        ];
    }

    #[DataProvider('headingsProvider')]
    public function test_ties_prepositions(string $input, string $expected): void
    {
        $this->assertSame($expected, RussianTypography::tiePrepositionsToNextWord($input));
    }
}
