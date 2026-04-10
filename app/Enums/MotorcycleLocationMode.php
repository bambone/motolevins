<?php

declare(strict_types=1);

namespace App\Enums;

enum MotorcycleLocationMode: string
{
    case Everywhere = 'everywhere';

    /** Ограничено выбранными локациями (пивот на карточке; при единицах — общий набор для всех). */
    case Selected = 'selected';

    /** Локации только у каждой единицы парка (требует uses_fleet_units). */
    case PerUnit = 'per_unit';

    public function label(): string
    {
        return match ($this) {
            self::Everywhere => 'Доступно везде',
            self::Selected => 'Только в выбранных локациях',
            self::PerUnit => 'Локации задаются для каждой единицы парка',
        };
    }
}
