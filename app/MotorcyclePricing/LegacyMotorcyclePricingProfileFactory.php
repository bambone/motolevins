<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;

/**
 * Migration bridge only: builds profile v1 JSON shape from legacy scalar columns.
 * Use in backfill commands — not as a permanent runtime dependency after cutover.
 */
final class LegacyMotorcyclePricingProfileFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function fromMotorcycle(Motorcycle $m): array
    {
        $id = (int) $m->id;
        $currency = MotorcyclePricingSchema::DEFAULT_CURRENCY;
        $tariffs = [];
        $sort = 10;

        $ppd = (int) ($m->price_per_day ?? 0);
        if ($ppd > 0) {
            $tariffs[] = [
                'id' => self::legacyTariffId('daily', $id),
                'label' => 'Сутки',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => PricingMinorMoney::majorToMinor($ppd),
                'applicability' => ['mode' => ApplicabilityMode::Always->value],
                'visibility' => [
                    'show_on_card' => true,
                    'show_on_detail' => true,
                    'show_in_quote' => true,
                ],
                'priority' => $sort,
                'sort_order' => $sort,
            ];
            $sort += 10;
        }

        $p23 = $m->price_2_3_days;
        if ($p23 !== null && (int) $p23 > 0) {
            $tariffs[] = [
                'id' => self::legacyTariffId('range23', $id),
                'label' => '2–3 суток',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => PricingMinorMoney::majorToMinor((int) $p23),
                'applicability' => [
                    'mode' => ApplicabilityMode::DurationRangeDays->value,
                    'min_days' => 2,
                    'max_days' => 3,
                ],
                'visibility' => [
                    'show_on_card' => false,
                    'show_on_detail' => true,
                    'show_in_quote' => true,
                ],
                'priority' => $sort,
                'sort_order' => $sort,
            ];
            $sort += 10;
        }

        $pw = $m->price_week;
        if ($pw !== null && (int) $pw > 0) {
            $perDay = (int) max(1, round((int) $pw / 7));
            $tariffs[] = [
                'id' => self::legacyTariffId('week', $id),
                'label' => 'От 7 суток',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => PricingMinorMoney::majorToMinor($perDay),
                'applicability' => [
                    'mode' => ApplicabilityMode::DurationMinDays->value,
                    'min_days' => 7,
                ],
                'visibility' => [
                    'show_on_card' => false,
                    'show_on_detail' => true,
                    'show_in_quote' => true,
                ],
                'priority' => $sort,
                'sort_order' => $sort,
            ];
            $sort += 10;
        }

        $primaryId = $ppd > 0 ? self::legacyTariffId('daily', $id) : ($tariffs[0]['id'] ?? null);

        $note = $m->catalog_price_note;

        return [
            'schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'pricing_mode' => 'simple',
            'currency' => $currency,
            'tariffs' => $tariffs,
            'display' => [
                'card_primary_tariff_id' => $primaryId,
                'card_secondary_mode' => 'none',
                'card_secondary_text' => null,
                'card_secondary_tariff_id' => null,
                'detail_tariffs_limit' => null,
            ],
            'financial_terms' => [
                'deposit_amount_minor' => null,
                'prepayment_amount_minor' => null,
                'catalog_price_note' => $note !== null && $note !== '' ? (string) $note : null,
            ],
        ];
    }

    public static function legacyTariffId(string $key, int $motorcycleId): string
    {
        return 'legacy-'.$key.'-'.$motorcycleId;
    }
}
