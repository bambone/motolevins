<?php

namespace App\Support\Typography;

/**
 * Связка коротких предлогов/союзов со следующим словом (неразрывный пробел),
 * чтобы не оставались «висячки» в конце строки (в, на, по, и …).
 */
final class RussianTypography
{
    private const NBSP = "\u{00A0}";

    /**
     * @var list<string> длиннее одного символа — раньше в alternation
     */
    private const MULTI_WORD_PREFIXES = [
        'без', 'безо', 'вне', 'для', 'или', 'из', 'ко', 'над', 'об', 'от', 'под', 'при', 'про', 'со', 'во', 'до', 'за', 'на', 'по', 'но', 'да',
    ];

    /**
     * @var list<string> один символ (кириллица)
     */
    private const SINGLE_CHAR_PREFIXES = [
        'в', 'к', 'с', 'у', 'о', 'и', 'а',
    ];

    public static function tiePrepositionsToNextWord(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        $parts = array_merge(
            self::MULTI_WORD_PREFIXES,
            self::SINGLE_CHAR_PREFIXES,
        );

        usort($parts, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $alt = implode('|', array_map(static function (string $w): string {
            return preg_quote($w, '/');
        }, $parts));

        // Только если дальше идёт буква (не цифра/скобка), чтобы не трогать «по 2» и т.п.
        $pattern = '/(?<=^|[\s'.self::NBSP.'])('.$alt.')\s+(?=\p{L})/iu';

        return (string) preg_replace($pattern, '$1'.self::NBSP, $text);
    }
}
