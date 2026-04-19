<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;
use App\Services\PricingService;

/**
 * Fills v2 pricing snapshot + scalar columns for new {@see \App\Models\Booking} rows.
 */
final class BookingPricingHydrator
{
    public function __construct(
        private readonly MotorcycleQuoteEngine $quoteEngine,
        private readonly BookingPricingSnapshotV2Assembler $assembler,
    ) {}

    /**
     * @param  array<string, mixed>  $pricingResult  Output of {@see PricingService::calculatePrice}
     * @param  list<array<string, mixed>>  $addonLines
     * @return array<string, mixed> Attributes to merge into {@see Booking::create}
     */
    public function bookingPricingAttributes(
        Motorcycle $motorcycle,
        int $days,
        array $pricingResult,
        array $addonLines,
    ): array {
        $days = max(1, $days);
        $quote = $this->quoteEngine->quoteForDays($motorcycle, $days);
        $addonsTotal = (int) ($pricingResult['addons_total'] ?? 0);
        $baseMajor = (int) ($pricingResult['base_price'] ?? 0);
        $depositMajor = (int) ($pricingResult['deposit'] ?? 0);
        $currency = (string) ($quote['currency'] ?? MotorcyclePricingSchema::DEFAULT_CURRENCY);
        if ($currency === '' && isset($pricingResult['pricing_snapshot']['currency'])) {
            $currency = (string) $pricingResult['pricing_snapshot']['currency'];
        }
        if ($currency === '') {
            $currency = MotorcyclePricingSchema::DEFAULT_CURRENCY;
        }

        $status = (string) ($quote['status'] ?? '');

        if ($status === 'ok') {
            $snapshot = $this->assembler->assembleOk($quote, $addonsTotal, $addonLines);
            $rentalMinor = (int) ($quote['totals']['rental_total_minor'] ?? 0);
            $payableNow = $snapshot['totals']['payable_now_minor'] ?? null;

            return [
                'pricing_snapshot_json' => $snapshot,
                'pricing_snapshot_schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
                'currency' => (string) ($snapshot['currency'] ?? $currency),
                'rental_total_minor' => $rentalMinor,
                'deposit_amount_minor' => $depositMajor > 0 ? PricingMinorMoney::majorToMinor($depositMajor) : null,
                'payable_now_minor' => $payableNow,
                'selected_tariff_id' => $quote['selected_tariff']['id'] ?? null,
                'selected_tariff_kind' => $quote['selected_tariff']['kind'] ?? null,
            ];
        }

        if ($status === 'on_request') {
            $snapshot = $this->assembler->assembleOnRequest($quote, $addonsTotal, $addonLines);
            $depMinor = $depositMajor > 0 ? PricingMinorMoney::majorToMinor($depositMajor) : null;
            $addonsMinor = PricingMinorMoney::majorToMinor(max(0, $addonsTotal));

            return [
                'pricing_snapshot_json' => $snapshot,
                'pricing_snapshot_schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
                'currency' => (string) ($snapshot['currency'] ?? $currency),
                'rental_total_minor' => 0,
                'deposit_amount_minor' => $depMinor,
                'payable_now_minor' => null,
                'selected_tariff_id' => $quote['selected_tariff']['id'] ?? null,
                'selected_tariff_kind' => $quote['selected_tariff']['kind'] ?? TariffKind::OnRequest->value,
            ];
        }

        $snapshot = $this->assembler->assembleFromLegacyComputation(
            rentalTotalMajor: $baseMajor,
            days: $days,
            addonsTotalMajor: $addonsTotal,
            addonLines: $addonLines,
            depositMajor: $depositMajor,
            currency: $currency,
        );
        $rentalMinor = (int) ($snapshot['totals']['rental_total_minor'] ?? 0);

        return [
            'pricing_snapshot_json' => $snapshot,
            'pricing_snapshot_schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
            'currency' => (string) ($snapshot['currency'] ?? $currency),
            'rental_total_minor' => $rentalMinor,
            'deposit_amount_minor' => $depositMajor > 0 ? PricingMinorMoney::majorToMinor($depositMajor) : null,
            'payable_now_minor' => null,
            'selected_tariff_id' => null,
            'selected_tariff_kind' => null,
        ];
    }
}
