<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Booking;
use App\Models\Motorcycle;
use Illuminate\Validation\ValidationException;

/**
 * Guards automatic creation of {@see Booking} rows that must carry an engine-backed rental total.
 */
final class MotorcycleBookingPricingPolicy
{
    public function __construct(
        private readonly MotorcycleQuoteEngine $engine,
    ) {}

    public function canMaterializeConfirmedBookingWithEngineTotal(Motorcycle $motorcycle, int $days): bool
    {
        $quote = $this->engine->quoteForDays($motorcycle, max(1, $days));

        return ($quote['status'] ?? '') === 'ok';
    }

    /**
     * Operator/materialized confirmed booking: only {@see MotorcycleQuoteEngine} status {@code ok} is allowed.
     *
     * @throws ValidationException
     */
    public function requireOkQuoteForConfirmedMaterialization(Motorcycle $motorcycle, int $days): void
    {
        if ($this->canMaterializeConfirmedBookingWithEngineTotal($motorcycle, $days)) {
            return;
        }

        $quote = $this->engine->quoteForDays($motorcycle, max(1, $days));
        $msg = match ((string) ($quote['status'] ?? '')) {
            'on_request' => 'Для этой модели цена «по запросу» — нельзя автоматически создать подтверждённую бронь с фиксированной суммой. Настройте тариф с автоматическим расчётом или оформите условия вручную.',
            default => 'Невозможно автоматически рассчитать стоимость для подтверждённой брони. Проверьте профиль тарифов мотоцикла.',
        };

        throw ValidationException::withMessages([
            'motorcycle_id' => $msg,
        ]);
    }

    /**
     * Public checkout: allow {@code ok} and {@code on_request}; with legacy scalar fallback off, block broken profiles.
     *
     * @throws \InvalidArgumentException
     */
    public function assertPublicCheckoutPricingResolvable(Motorcycle $motorcycle, int $days): void
    {
        $quote = $this->engine->quoteForDays($motorcycle, max(1, $days));
        $st = (string) ($quote['status'] ?? '');
        if ($st === 'ok' || $st === 'on_request') {
            return;
        }
        if (config('pricing.legacy_scalar_price_fallback', true)) {
            return;
        }

        throw new \InvalidArgumentException(__('Невозможно оформить бронирование: проверьте тарифы техники или обратитесь к администратору.'));
    }
}
