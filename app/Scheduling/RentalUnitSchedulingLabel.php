<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\RentalUnit;

/**
 * Единая стабильная подпись для linked scheduling (title, SchedulingTarget.label, binding_label, API).
 */
final class RentalUnitSchedulingLabel
{
    public static function label(RentalUnit $unit): string
    {
        $unit->loadMissing('motorcycle');
        $motoName = $unit->motorcycle?->name ?? '—';
        $idPart = filled($unit->external_id) ? (string) $unit->external_id : '#'.$unit->id;

        return trim($idPart.' · '.$motoName);
    }
}
