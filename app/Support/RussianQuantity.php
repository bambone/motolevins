<?php

namespace App\Support;

/**
 * Russian cardinal phrases for admin summaries (1 объект / 2 объекта / 5 объектов).
 */
final class RussianQuantity
{
    /**
     * @param  array{0: string, 1: string, 2: string}  $oneFewMany  e.g. ['шаг', 'шага', 'шагов']
     */
    public static function fewMany(int $n, string $one, string $few, string $many): string
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return $many;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $few;
        }
        if ($n1 === 1) {
            return $one;
        }

        return $many;
    }
}
