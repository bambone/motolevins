<?php

declare(strict_types=1);

namespace App\Booking;

use App\Models\Motorcycle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Единое правило: техника доступна для публичного онлайн-бронирования (каталог + статус).
 */
final class PublicBookingMotorcyclePolicy
{
    public static function isAllowedForPublicBooking(Motorcycle $motorcycle): bool
    {
        return (bool) $motorcycle->show_in_catalog && $motorcycle->status === 'available';
    }

    /**
     * Ограничение запроса моделей для публичного каталога / листинга (дрейф с ручными where в контроллерах).
     *
     * @param  Builder<Motorcycle>  $query
     * @return Builder<Motorcycle>
     */
    public static function constrainEligibleForPublicBooking(Builder $query): Builder
    {
        return $query->where('show_in_catalog', true)->where('status', 'available');
    }
}
