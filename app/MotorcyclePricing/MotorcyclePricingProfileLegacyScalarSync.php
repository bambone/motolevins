<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;
use Illuminate\Support\Facades\Schema;

/**
 * Until legacy columns are dropped: keep price_per_day / bundle fields aligned with profile for consumers not yet migrated.
 */
final class MotorcyclePricingProfileLegacyScalarSync
{
    public static function syncFromProfile(Motorcycle $motorcycle): void
    {
        if (! Schema::hasColumn($motorcycle->getTable(), 'price_per_day')) {
            return;
        }

        $profile = $motorcycle->pricing_profile_json;
        if (! is_array($profile) || $profile === []) {
            return;
        }

        $tariffs = is_array($profile['tariffs'] ?? null) ? $profile['tariffs'] : [];
        $display = is_array($profile['display'] ?? null) ? $profile['display'] : [];
        $fin = is_array($profile['financial_terms'] ?? null) ? $profile['financial_terms'] : [];

        $primaryId = (string) ($display['card_primary_tariff_id'] ?? '');
        $primary = self::findTariff($tariffs, $primaryId);

        $pricePerDay = 0;
        $price23 = null;
        $priceWeek = null;

        if (is_array($primary)) {
            $kind = TariffKind::tryFrom((string) ($primary['kind'] ?? ''));
            if ($kind === TariffKind::FixedPerDay && isset($primary['amount_minor'])) {
                $pricePerDay = PricingMinorMoney::minorToMajor((int) $primary['amount_minor']);
            } elseif ($kind === TariffKind::FixedPerRental && isset($primary['amount_minor'])) {
                $pricePerDay = PricingMinorMoney::minorToMajor((int) $primary['amount_minor']);
            }
        }

        foreach ($tariffs as $t) {
            if (! is_array($t)) {
                continue;
            }
            $kind = TariffKind::tryFrom((string) ($t['kind'] ?? ''));
            if ($kind !== TariffKind::FixedPerDay || ! isset($t['amount_minor'])) {
                continue;
            }
            $app = is_array($t['applicability'] ?? null) ? $t['applicability'] : [];
            $mode = ApplicabilityMode::tryFrom((string) ($app['mode'] ?? ''));
            $major = PricingMinorMoney::minorToMajor((int) $t['amount_minor']);
            if ($mode === ApplicabilityMode::DurationRangeDays
                && (int) ($app['min_days'] ?? 0) === 2
                && (int) ($app['max_days'] ?? 0) === 3) {
                $price23 = $major;
            }
            if ($mode === ApplicabilityMode::DurationMinDays && (int) ($app['min_days'] ?? 0) === 7) {
                $priceWeek = $major * 7;
            }
        }

        if ($pricePerDay === 0 && $tariffs !== []) {
            foreach ($tariffs as $t) {
                if (! is_array($t)) {
                    continue;
                }
                if (TariffKind::tryFrom((string) ($t['kind'] ?? '')) !== TariffKind::FixedPerDay) {
                    continue;
                }
                $app = is_array($t['applicability'] ?? null) ? $t['applicability'] : [];
                if (ApplicabilityMode::tryFrom((string) ($app['mode'] ?? '')) !== ApplicabilityMode::Always) {
                    continue;
                }
                if (isset($t['amount_minor'])) {
                    $pricePerDay = PricingMinorMoney::minorToMajor((int) $t['amount_minor']);
                    break;
                }
            }
        }

        $note = isset($fin['catalog_price_note']) ? (string) $fin['catalog_price_note'] : null;

        $motorcycle->forceFill([
            'price_per_day' => $pricePerDay,
            'price_2_3_days' => $price23,
            'price_week' => $priceWeek,
            'catalog_price_note' => $note !== null && $note !== '' ? $note : null,
        ])->saveQuietly();
    }

    /**
     * @param  list<array<string, mixed>>  $tariffs
     * @return ?array<string, mixed>
     */
    private static function findTariff(array $tariffs, string $id): ?array
    {
        if ($id === '') {
            return null;
        }
        foreach ($tariffs as $t) {
            if (is_array($t) && (string) ($t['id'] ?? '') === $id) {
                return $t;
            }
        }

        return null;
    }
}
