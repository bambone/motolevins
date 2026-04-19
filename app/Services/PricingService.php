<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Motorcycle;
use App\Models\PricingRule;
use App\Models\RentalUnit;
use App\MotorcyclePricing\MotorcyclePricingProfileLoader;
use App\MotorcyclePricing\MotorcycleQuoteEngine;
use App\MotorcyclePricing\PricingMinorMoney;
use App\MotorcyclePricing\RentalPricingDuration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PricingService
{
    /**
     * Calculate price for a rental period.
     */
    public function calculatePrice(
        Motorcycle|RentalUnit $target,
        Carbon $start,
        Carbon $end,
        string $rentalType = 'daily',
        array $addonIds = []
    ): array {
        $motorcycle = $target instanceof Motorcycle ? $target : $target->motorcycle;
        $rentalUnit = $target instanceof RentalUnit ? $target : null;

        $days = RentalPricingDuration::inclusiveCalendarDays($start, $end);
        $basePrice = $this->getBasePrice($motorcycle, $rentalUnit, $rentalType, $days, $start);

        $addonsTotal = 0;
        $addonsSnapshot = [];

        $tenantId = (int) $motorcycle->tenant_id;
        foreach ($addonIds as $addonId => $qty) {
            $addon = Addon::query()
                ->where('tenant_id', $tenantId)
                ->whereKey((int) $addonId)
                ->where('is_active', true)
                ->first();
            if ($addon && $qty > 0) {
                $lineTotal = $addon->price * $qty;
                $addonsTotal += $lineTotal;
                $addonsSnapshot[] = [
                    'addon_id' => $addon->id,
                    'name' => $addon->name,
                    'quantity' => $qty,
                    'price' => $addon->price,
                    'total' => $lineTotal,
                ];
            }
        }

        $deposit = $this->getDeposit($motorcycle, $rentalUnit, $rentalType);

        return [
            'base_price' => $basePrice,
            'days' => $days,
            'rental_type' => $rentalType,
            'addons' => $addonsSnapshot,
            'addons_total' => $addonsTotal,
            'deposit' => $deposit,
            'total' => $basePrice + $addonsTotal,
            'pricing_snapshot' => [
                'base_price' => $basePrice,
                'days' => $days,
                'addons' => $addonsSnapshot,
                'deposit' => $deposit,
            ],
        ];
    }

    protected function getBasePrice(?Motorcycle $motorcycle, ?RentalUnit $rentalUnit, string $rentalType, int $days, Carbon $date): int
    {
        if ($motorcycle !== null && $rentalType === 'daily') {
            $quote = app(MotorcycleQuoteEngine::class)->quoteForDays($motorcycle, max(1, $days));
            if (($quote['status'] ?? '') === 'ok') {
                $minor = (int) ($quote['totals']['rental_total_minor'] ?? 0);

                return PricingMinorMoney::minorToMajor($minor);
            }

            if (! config('pricing.legacy_scalar_price_fallback', true)) {
                return 0;
            }
        }

        $rules = $this->getApplicableRules($motorcycle, $rentalUnit, $rentalType, $days);

        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $date, $days)) {
                return $rule->price * $days;
            }
        }

        if ($motorcycle && Schema::hasColumn($motorcycle->getTable(), 'price_per_day')) {
            return match ($rentalType) {
                'weekly' => (int) (($motorcycle->price_week ?? $motorcycle->price_per_day * 7) * ceil($days / 7)),
                'hourly' => (int) (($motorcycle->price_per_day ?? 0) / 24 * $days * 24),
                default => (int) (($motorcycle->price_per_day ?? 0) * $days),
            };
        }

        return 0;
    }

    protected function getApplicableRules(?Motorcycle $motorcycle, ?RentalUnit $rentalUnit, string $rentalType, int $days): Collection
    {
        $tenantId = \currentTenant()?->id;
        if (! $tenantId) {
            return collect();
        }

        $q = PricingRule::query()
            ->where('tenant_id', $tenantId)
            ->where('rental_type', $rentalType)
            ->where('is_active', true)
            ->where('min_duration', '<=', $days)
            ->where(function ($q) use ($days) {
                $q->whereNull('max_duration')->orWhere('max_duration', '>=', $days);
            });

        if ($rentalUnit) {
            $q->where(function ($q) use ($rentalUnit, $motorcycle) {
                $q->where('rental_unit_id', $rentalUnit->id)
                    ->orWhere(function ($q2) use ($motorcycle) {
                        $q2->whereNull('rental_unit_id')->where('motorcycle_id', $motorcycle?->id);
                    });
            });
        } elseif ($motorcycle) {
            $q->where(function ($q) use ($motorcycle) {
                $q->where('motorcycle_id', $motorcycle->id)->orWhereNull('motorcycle_id');
            });
        }

        return $q->orderByDesc('priority')->get();
    }

    protected function ruleMatches(PricingRule $rule, Carbon $date, int $days): bool
    {
        if ($rule->day_of_week !== null && (int) $rule->day_of_week !== (int) $date->dayOfWeek) {
            return false;
        }

        return true;
    }

    protected function getDeposit(?Motorcycle $motorcycle, ?RentalUnit $rentalUnit, string $rentalType): int
    {
        if ($motorcycle !== null) {
            $profile = app(MotorcyclePricingProfileLoader::class)->loadOrSynthesize($motorcycle);
            $fin = is_array($profile['financial_terms'] ?? null) ? $profile['financial_terms'] : [];
            if (isset($fin['deposit_amount_minor']) && $fin['deposit_amount_minor'] !== null) {
                $m = (int) $fin['deposit_amount_minor'];
                if ($m > 0) {
                    return PricingMinorMoney::minorToMajor($m);
                }
            }
        }

        $tenantId = \currentTenant()?->id;
        if (! $tenantId) {
            return 0;
        }

        $rule = PricingRule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('deposit', '>', 0)
            ->when($rentalUnit, fn ($q) => $q->where('rental_unit_id', $rentalUnit->id))
            ->when($motorcycle, fn ($q) => $q->where('motorcycle_id', $motorcycle->id)->orWhereNull('motorcycle_id'))
            ->orderByDesc('priority')
            ->first();

        return $rule?->deposit ?? 0;
    }
}
