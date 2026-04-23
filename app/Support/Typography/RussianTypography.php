<?php

namespace App\Support\Typography;

/**
 * Связка коротких предлогов/союзов/частицы «не» со следующим словом (неразрывный пробел),
 * чтобы не оставались «висячки» в конце строки; тире «—» с соседними словами; см. {@see self::wrapPhrase}.
 */
final class RussianTypography
{
    private const NBSP = "\u{00A0}";

    /**
     * @var list<string> длиннее одного символа — раньше в alternation
     */
    private const MULTI_WORD_PREFIXES = [
        'без', 'безо', 'вне', 'для', 'или', 'из', 'ко', 'над', 'об', 'от', 'под', 'при', 'про', 'со', 'во', 'до', 'за', 'на', 'по', 'но', 'да', 'не',
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

        // Дальше — начало слова: буква или типографские кавычки/скобка перед буквой (не «по 2»).
        $afterPrefix = '(?=(?:[«"„(]*\p{L}))';
        $pattern = '/(?<=^|[\s'.self::NBSP.'])('.$alt.')\s+'.$afterPrefix.'/iu';

        $text = (string) preg_replace($pattern, '$1'.self::NBSP, $text);

        // Тире «—» не отрываем от предыдущего слова.
        $text = preg_replace('/\s+—/u', self::NBSP.'—', $text);

        // Тире «—» не отрываем от следующего: слово, цифра или открывающая кавычка.
        $text = (string) preg_replace('/—\s+(?=[\p{L}\p{N}«"„(])/u', '—'.self::NBSP, (string) $text);

        return $text;
    }

    /**
     * Оборачивает первое вхождение фразы (с обычными или неразрывными пробелами) в &lt;span class="…"&gt;.
     * Сопоставление по токенам, разделённым пробелами в $plainPhrase (запятые и косые — часть токена).
     */
    public static function wrapPhrase(
        string $typographed,
        string $plainPhrase,
        string $class = 'font-medium text-slate-800',
    ): string {
        $typographed = trim($typographed);
        $plainPhrase = trim($plainPhrase);
        if ($typographed === '' || $plainPhrase === '') {
            return $typographed;
        }
        $parts = preg_split('/\s+/u', $plainPhrase, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return $typographed;
        }
        $q = array_map(
            static fn (string $p) => preg_quote($p, '/'),
            $parts
        );
        $flex = '(?:[ \x{00A0}]+)';
        $pattern = '/(' . implode($flex, $q) . ')/u';
        $classAttr = htmlspecialchars($class, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $replacement = '<span class="' . $classAttr . '">$1</span>';
        $replaced = preg_replace($pattern, $replacement, $typographed, 1);
        if ($replaced === null || $replaced === '') {
            return $typographed;
        }

        return $replaced;
    }

    /**
     * То же для текста с переводами строк (например, подписи в настройках).
     *
     * @param  non-empty-string  $separator  символ(и) объединения строк после обработки
     */
    public static function tiePrepositionsPerLine(string $text, string $separator = "\n"): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }
        $lines = preg_split('/\R/u', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $out[] = self::tiePrepositionsToNextWord(trim((string) $line));
        }

        return implode($separator, $out);
    }
}
