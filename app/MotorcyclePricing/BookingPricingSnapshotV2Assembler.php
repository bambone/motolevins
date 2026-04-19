<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

/**
 * Builds pricing_snapshot_json for {@see MotorcyclePricingSchema::SNAPSHOT_VERSION}.
 */
final class BookingPricingSnapshotV2Assembler
{
    /**
     * @param  array<string, mixed>  $motorcycleQuote  Result of {@see MotorcycleQuoteEngine::quoteForDays} when status ok
     * @param  list<array<string, mixed>>  $addonLines  Addon snapshot lines (major unit prices as in legacy)
     * @return array<string, mixed>
     */
    public function assembleOk(array $motorcycleQuote, int $addonsTotalMajor, array $addonLines = []): array
    {
        $rentalMinor = (int) ($motorcycleQuote['totals']['rental_total_minor'] ?? 0);
        $addonsMinor = PricingMinorMoney::majorToMinor(max(0, $addonsTotalMajor));
        $grandMinor = $rentalMinor + $addonsMinor;

        $lines = $motorcycleQuote['lines'] ?? [];
        foreach ($addonLines as $row) {
            $lines[] = [
                'type' => 'addon',
                'label' => (string) ($row['name'] ?? 'Дополнение'),
                'unit_amount_minor' => PricingMinorMoney::majorToMinor((int) ($row['price'] ?? 0)),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'line_total_minor' => PricingMinorMoney::majorToMinor((int) ($row['total'] ?? 0)),
                'addon_id' => $row['addon_id'] ?? null,
            ];
        }

        $depositMinor = $motorcycleQuote['financial_terms']['deposit_amount_minor'] ?? null;
        $prepayMinor = $motorcycleQuote['financial_terms']['prepayment_amount_minor'] ?? null;
        $payableNow = ($prepayMinor !== null && (int) $prepayMinor > 0) ? (int) $prepayMinor : null;

        return [
            'schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
            'profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'selected_tariff_id' => $motorcycleQuote['selected_tariff']['id'] ?? null,
            'selected_tariff_label' => $motorcycleQuote['selected_tariff']['label'] ?? null,
            'quote_kind' => $motorcycleQuote['selected_tariff']['kind'] ?? null,
            'duration_days' => $motorcycleQuote['duration']['days'] ?? null,
            'currency' => $motorcycleQuote['currency'] ?? MotorcyclePricingSchema::DEFAULT_CURRENCY,
            'lines' => $lines,
            'financial_terms' => [
                'deposit_amount_minor' => $depositMinor,
                'prepayment_amount_minor' => $prepayMinor,
            ],
            'totals' => [
                'rental_total_minor' => $rentalMinor,
                'addons_total_minor' => $addonsMinor,
                'payable_now_minor' => $payableNow,
                'grand_total_minor' => $grandMinor,
            ],
            'notes' => [],
        ];
    }

    /**
     * When quote engine is not {@see MotorcycleQuoteEngine} ok (legacy rules, invalid profile, on_request, etc.).
     *
     * @param  list<array<string, mixed>>  $addonLines
     * @return array<string, mixed>
     */
    public function assembleFromLegacyComputation(
        int $rentalTotalMajor,
        int $days,
        int $addonsTotalMajor,
        array $addonLines,
        int $depositMajor,
        string $currency,
    ): array {
        $rentalMinor = PricingMinorMoney::majorToMinor(max(0, $rentalTotalMajor));
        $addonsMinor = PricingMinorMoney::majorToMinor(max(0, $addonsTotalMajor));
        $grandMinor = $rentalMinor + $addonsMinor;
        $depositMinor = $depositMajor > 0 ? PricingMinorMoney::majorToMinor($depositMajor) : null;

        $lines = [];
        if ($rentalMinor > 0 && $days > 0) {
            $perDay = intdiv($rentalMinor, $days);
            $lines[] = [
                'type' => 'rental',
                'label' => 'Аренда',
                'unit_amount_minor' => $perDay,
                'quantity' => $days,
                'line_total_minor' => $rentalMinor,
                'legacy_computed' => true,
            ];
        } elseif ($rentalMinor > 0) {
            $lines[] = [
                'type' => 'rental',
                'label' => 'Аренда',
                'unit_amount_minor' => $rentalMinor,
                'quantity' => 1,
                'line_total_minor' => $rentalMinor,
                'legacy_computed' => true,
            ];
        }

        foreach ($addonLines as $row) {
            $lines[] = [
                'type' => 'addon',
                'label' => (string) ($row['name'] ?? 'Дополнение'),
                'unit_amount_minor' => PricingMinorMoney::majorToMinor((int) ($row['price'] ?? 0)),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'line_total_minor' => PricingMinorMoney::majorToMinor((int) ($row['total'] ?? 0)),
                'addon_id' => $row['addon_id'] ?? null,
            ];
        }

        return [
            'schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
            'profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'selected_tariff_id' => null,
            'selected_tariff_label' => null,
            'quote_kind' => null,
            'duration_days' => max(1, $days),
            'currency' => $currency !== '' ? $currency : MotorcyclePricingSchema::DEFAULT_CURRENCY,
            'lines' => $lines,
            'financial_terms' => [
                'deposit_amount_minor' => $depositMinor,
                'prepayment_amount_minor' => null,
            ],
            'totals' => [
                'rental_total_minor' => $rentalMinor,
                'addons_total_minor' => $addonsMinor,
                'payable_now_minor' => null,
                'grand_total_minor' => $grandMinor,
            ],
            'notes' => ['legacy_computed_total'],
        ];
    }

    /**
     * @param  array<string, mixed>  $onRequestQuote  {@see MotorcycleQuoteEngine} on_request payload
     * @param  list<array<string, mixed>>  $addonLines
     * @return array<string, mixed>
     */
    public function assembleOnRequest(array $onRequestQuote, int $addonsTotalMajor, array $addonLines): array
    {
        $addonsMinor = PricingMinorMoney::majorToMinor(max(0, $addonsTotalMajor));
        $lines = [];
        foreach ($addonLines as $row) {
            $lines[] = [
                'type' => 'addon',
                'label' => (string) ($row['name'] ?? 'Дополнение'),
                'unit_amount_minor' => PricingMinorMoney::majorToMinor((int) ($row['price'] ?? 0)),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'line_total_minor' => PricingMinorMoney::majorToMinor((int) ($row['total'] ?? 0)),
                'addon_id' => $row['addon_id'] ?? null,
            ];
        }

        $currency = (string) ($onRequestQuote['currency'] ?? MotorcyclePricingSchema::DEFAULT_CURRENCY);

        return [
            'schema_version' => MotorcyclePricingSchema::SNAPSHOT_VERSION,
            'profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'selected_tariff_id' => $onRequestQuote['selected_tariff']['id'] ?? null,
            'selected_tariff_label' => $onRequestQuote['selected_tariff']['label'] ?? null,
            'quote_kind' => $onRequestQuote['selected_tariff']['kind'] ?? TariffKind::OnRequest->value,
            'duration_days' => null,
            'currency' => $currency,
            'lines' => $lines,
            'financial_terms' => [
                'deposit_amount_minor' => null,
                'prepayment_amount_minor' => null,
            ],
            'totals' => [
                'rental_total_minor' => 0,
                'addons_total_minor' => $addonsMinor,
                'payable_now_minor' => null,
                'grand_total_minor' => $addonsMinor,
            ],
            'notes' => ['on_request_rental'],
        ];
    }
}
