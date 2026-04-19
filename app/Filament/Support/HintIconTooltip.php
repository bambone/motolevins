<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\Concerns\HasHint;
use Illuminate\Support\HtmlString;

/**
 * Многострочные подсказки для {@see HasHint::hintIconTooltip()}.
 *
 * Filament отдаёт тултип в `getHintIconTooltip(): ?string`, поэтому {@see HtmlString}
 * приводится к строке и теряется `Htmlable` → в разметке тултипа выключается `allowHTML`, и литералы
 * «&lt;br&gt;» видны как текст. Здесь переводы строк `\n` плюс CSS в
 * `filament-ghost-modal-overlay.css`: у темы Filament у `.tippy-box` стоит `white-space: normal`,
 * без переопределения переносы схлопываются.
 */
final class HintIconTooltip
{
    /**
     * @param  non-empty-array<int, string>  $lines
     */
    public static function lines(string ...$lines): string
    {
        return implode("\n", array_map(static fn (string $line): string => e($line), $lines));
    }
}
