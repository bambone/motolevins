<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum AvailabilityRuleType: string
{
    case WeeklyOpen = 'weekly_open';

    case WeeklyClosed = 'weekly_closed';

    public function label(): string
    {
        return match ($this) {
            self::WeeklyOpen => 'Окно приёма (когда можно записаться)',
            self::WeeklyClosed => 'Перерыв или «закрыто» (вычесть из окна)',
        };
    }

    /** Подсказка под полем «Тип» в форме. */
    public function formHelperText(): string
    {
        return match ($this) {
            self::WeeklyOpen => 'Добавляет интервал, в который слоты разрешены. Несколько окон в один день — несколько правил с тем же днём недели.',
            self::WeeklyClosed => 'Убирает интервал из уже открытых часов (обед, личное время). Сначала задайте окна «Окно приёма», затем добавьте перерывы.',
        };
    }
}
