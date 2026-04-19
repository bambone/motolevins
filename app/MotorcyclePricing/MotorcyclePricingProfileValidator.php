<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

/**
 * Form-time validation (save). Ambiguous auto-quote resolution for a specific duration is enforced in {@see MotorcycleTariffResolver} + {@see MotorcycleQuoteEngine}.
 */
final class MotorcyclePricingProfileValidator
{
    /**
     * @param  array<string, mixed>  $profile
     * @return array{validity: PricingProfileValidity, warnings: list<string>, errors: list<string>}
     */
    public function validate(array $profile): array
    {
        $warnings = [];
        $errors = [];

        $version = (int) ($profile['schema_version'] ?? 0);
        if ($version !== MotorcyclePricingSchema::PROFILE_VERSION) {
            $errors[] = 'unsupported_or_missing_schema_version';
        }

        $tariffs = $profile['tariffs'] ?? null;
        if (! is_array($tariffs) || $tariffs === []) {
            $errors[] = 'no_tariffs';
        }

        $display = $profile['display'] ?? [];
        $primaryId = is_array($display) ? ($display['card_primary_tariff_id'] ?? null) : null;
        $ids = [];
        if (is_array($tariffs)) {
            foreach ($tariffs as $t) {
                if (! is_array($t)) {
                    continue;
                }
                $tid = (string) ($t['id'] ?? '');
                if ($tid === '') {
                    $errors[] = 'tariff_missing_id';

                    continue;
                }
                $ids[$tid] = true;
            }
        }

        if (is_string($primaryId) && $primaryId !== '' && $tariffs !== []) {
            if (! isset($ids[$primaryId])) {
                $errors[] = 'primary_tariff_missing';
            }
        } elseif ($tariffs !== []) {
            $errors[] = 'primary_tariff_not_set';
        }

        $secondaryMode = is_array($display) ? (string) ($display['card_secondary_mode'] ?? 'none') : 'none';
        $secondaryTid = is_array($display) ? (string) ($display['card_secondary_tariff_id'] ?? '') : '';
        if (in_array($secondaryMode, ['tariff', 'secondary_tariff'], true) && $secondaryTid !== '' && is_array($tariffs) && $tariffs !== []) {
            if (! isset($ids[$secondaryTid])) {
                $errors[] = 'secondary_tariff_missing';
            }
        }

        if (is_array($tariffs)) {
            foreach ($tariffs as $t) {
                if (! is_array($t)) {
                    continue;
                }
                $kind = (string) ($t['kind'] ?? '');
                if ($kind === TariffKind::OnRequest->value) {
                    $note = (string) ($t['note'] ?? '');
                    if ($note === '') {
                        $warnings[] = 'on_request_without_note:'.$t['id'];
                    }
                }
            }
        }

        $validity = PricingProfileValidity::Valid;
        if ($errors !== []) {
            $validity = PricingProfileValidity::Invalid;
        } elseif ($warnings !== []) {
            $validity = PricingProfileValidity::ValidWithWarnings;
        }

        return [
            'validity' => $validity,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * In-memory validity only (no DB column).
     */
    public function validityState(array $profile): PricingProfileValidity
    {
        return $this->validate($profile)['validity'];
    }

    /**
     * Hard gate before persisting pricing_profile_json (Filament / Livewire).
     *
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    public function blockingErrorsForSave(array $profile): array
    {
        $v = $this->validate($profile);
        $errors = $v['errors'];

        $tariffs = is_array($profile['tariffs'] ?? null) ? $profile['tariffs'] : [];
        foreach ($tariffs as $t) {
            if (! is_array($t)) {
                continue;
            }
            if (($t['kind'] ?? '') === TariffKind::OnRequest->value) {
                $note = trim((string) ($t['note'] ?? ''));
                if ($note === '') {
                    $errors[] = 'on_request_tariff_requires_note:'.(string) ($t['id'] ?? '');
                }
            }
        }

        if ($tariffs !== [] && $this->allTariffsHiddenEverywhere($tariffs)) {
            $errors[] = 'all_tariffs_hidden';
        }

        foreach ($this->overlappingDurationRangePairErrors($tariffs) as $e) {
            $errors[] = $e;
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  list<array<string, mixed>>  $tariffs
     */
    private function allTariffsHiddenEverywhere(array $tariffs): bool
    {
        $any = false;
        foreach ($tariffs as $t) {
            if (! is_array($t)) {
                continue;
            }
            $any = true;
            $vis = is_array($t['visibility'] ?? null) ? $t['visibility'] : [];
            $card = (bool) ($vis['show_on_card'] ?? false);
            $detail = (bool) ($vis['show_on_detail'] ?? true);
            $quote = (bool) ($vis['show_in_quote'] ?? true);
            if ($card || $detail || $quote) {
                return false;
            }
        }

        return $any;
    }

    /**
     * @param  list<array<string, mixed>>  $tariffs
     * @return list<string>
     */
    private function overlappingDurationRangePairErrors(array $tariffs): array
    {
        $ranges = [];
        foreach ($tariffs as $t) {
            if (! is_array($t)) {
                continue;
            }
            $app = is_array($t['applicability'] ?? null) ? $t['applicability'] : [];
            if (($app['mode'] ?? '') !== ApplicabilityMode::DurationRangeDays->value) {
                continue;
            }
            $min = max(1, (int) ($app['min_days'] ?? 1));
            $max = max($min, (int) ($app['max_days'] ?? $min));
            $ranges[] = [
                'id' => (string) ($t['id'] ?? ''),
                'min' => $min,
                'max' => $max,
            ];
        }

        $errors = [];
        $c = count($ranges);
        for ($i = 0; $i < $c; $i++) {
            for ($j = $i + 1; $j < $c; $j++) {
                $a = $ranges[$i];
                $b = $ranges[$j];
                $lo = max($a['min'], $b['min']);
                $hi = min($a['max'], $b['max']);
                if ($lo <= $hi) {
                    $errors[] = 'overlapping_duration_ranges:'.$a['id'].':'.$b['id'];
                }
            }
        }

        return $errors;
    }
}
