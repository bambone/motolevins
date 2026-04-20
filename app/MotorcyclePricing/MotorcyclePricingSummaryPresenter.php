<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Money\MoneyBindingRegistry;

/**
 * View-layer DTO builder: card line, detail list, SEO candidate (no raw profile leakage).
 *
 * {@see present()}: `detail_tariffs` and `detail_price_rows` are truncated to `display.detail_tariffs_limit` when it is a positive integer.
 */
final class MotorcyclePricingSummaryPresenter
{
    public function __construct(
        private readonly MotorcyclePricingProfileLoader $loader,
        private readonly MotorcyclePricingProfileValidator $validator,
    ) {}

    /**
     * @return array{
     *   card_price_label: ?string,
     *   card_price_minor: ?int,
     *   card_price_text: string,
     *   card_price_suffix: string,
     *   card_show_leading_from: bool,
     *   card_is_on_request: bool,
     *   card_hint: ?string,
     *   card_secondary_text: string,
     *   card_profile_invalid: bool,
     *   detail_tariffs: list<array{label: string, minor: ?int, kind: string, id: string}>,
     *   detail_price_rows: list<array{line: string, hint: ?string}>,
     *   financial_note: ?string,
     *   seo_offer_candidate: ?array{minor: int, currency: string, label: string, price_descriptor: string},
     *   validity: PricingProfileValidity
     * }
     */
    public function present(Motorcycle $motorcycle, ?Tenant $tenant = null): array
    {
        $profile = $this->loader->loadOrSynthesize($motorcycle);
        $v = $this->validator->validate($profile);

        $currency = (string) ($profile['currency'] ?? MotorcyclePricingSchema::DEFAULT_CURRENCY);
        $tariffs = is_array($profile['tariffs'] ?? null) ? $profile['tariffs'] : [];
        $display = is_array($profile['display'] ?? null) ? $profile['display'] : [];
        $fin = is_array($profile['financial_terms'] ?? null) ? $profile['financial_terms'] : [];

        $primaryId = (string) ($display['card_primary_tariff_id'] ?? '');
        $primary = $this->findTariff($tariffs, $primaryId);
        $secondaryMode = MotorcyclePricingProfileFormHydrator::normalizeCardSecondaryMode(
            (string) ($display['card_secondary_mode'] ?? 'none'),
        );

        $cardMinor = null;
        $cardLabel = '';
        $cardOnRequest = false;
        $primaryKind = null;
        if (is_array($primary)) {
            $primaryKind = TariffKind::tryFrom((string) ($primary['kind'] ?? ''));
            if ($primaryKind === TariffKind::OnRequest) {
                $cardOnRequest = true;
            } elseif ($primaryKind !== null && $primaryKind !== TariffKind::Informational) {
                $cardMinor = isset($primary['amount_minor']) ? (int) $primary['amount_minor'] : null;
            }
            if ($primaryKind === TariffKind::Informational) {
                $cardLabel = self::labelWithOptionalParenthetical(
                    trim((string) ($primary['label'] ?? '')),
                    trim((string) ($primary['catalog_public_hint'] ?? '')),
                );
            } else {
                $cardLabel = (string) ($primary['label'] ?? '');
            }
        }

        $hint = null;
        if ($secondaryMode === 'hint_text') {
            $hint = (string) ($display['card_secondary_text'] ?? '');
            if ($hint === '') {
                $hint = null;
            }
        }

        $secondaryTariffLine = '';
        if ($secondaryMode === 'secondary_tariff') {
            $secId = (string) ($display['card_secondary_tariff_id'] ?? '');
            $sec = $this->findTariff($tariffs, $secId);
            if (is_array($sec)) {
                $secondaryTariffLine = $this->formatTariffCatalogLine($sec, $tenant);
            }
        }

        $cardSecondaryText = $hint ?? $secondaryTariffLine;

        $invalid = $v['validity'] === PricingProfileValidity::Invalid;

        $primaryDayUnit = is_array($primary) && $primaryKind === TariffKind::FixedPerDay
            ? TariffCatalogDayUnit::fromProfile($primary['catalog_day_unit'] ?? null)
            : null;

        [$cardPriceText, $cardPriceSuffix, $cardShowLeadingFrom] = $this->buildCardPriceParts(
            $invalid,
            $cardOnRequest,
            $primaryKind,
            $cardMinor,
            $cardLabel,
            $tenant,
            $primaryDayUnit,
        );

        $detail = [];
        foreach ($tariffs as $t) {
            if (! is_array($t)) {
                continue;
            }
            $vis = is_array($t['visibility'] ?? null) ? $t['visibility'] : [];
            if (! ($vis['show_on_detail'] ?? false)) {
                continue;
            }
            $app = is_array($t['applicability'] ?? null) ? $t['applicability'] : [];
            $detail[] = [
                'id' => (string) ($t['id'] ?? ''),
                'label' => (string) ($t['label'] ?? ''),
                'kind' => (string) ($t['kind'] ?? ''),
                'minor' => isset($t['amount_minor']) ? (int) $t['amount_minor'] : null,
                'applicability_mode' => (string) ($app['mode'] ?? ApplicabilityMode::Always->value),
                'min_days' => isset($app['min_days']) ? (int) $app['min_days'] : 1,
                'max_days' => isset($app['max_days']) ? (int) $app['max_days'] : 3,
                'catalog_day_unit' => TariffCatalogDayUnit::fromProfile($t['catalog_day_unit'] ?? null),
                'catalog_public_hint' => trim((string) ($t['catalog_public_hint'] ?? '')),
            ];
        }

        $detailLimit = self::normalizeDetailTariffsLimit($display['detail_tariffs_limit'] ?? null);
        $detail = self::applyDetailTariffsLimit($detail, $detailLimit);
        $detailPriceRows = $this->buildDetailPriceRows($detail, $tenant);

        $seo = $this->buildSeoOfferCandidate($primary, $currency, $v['validity']);

        return [
            'card_price_label' => $cardLabel !== '' ? $cardLabel : null,
            'card_price_minor' => $cardMinor,
            'card_price_text' => $cardPriceText,
            'card_price_suffix' => $cardPriceSuffix,
            'card_show_leading_from' => $cardShowLeadingFrom,
            'card_is_on_request' => $cardOnRequest,
            'card_hint' => $hint,
            'card_secondary_text' => $cardSecondaryText,
            'card_profile_invalid' => $invalid,
            'detail_tariffs' => $detail,
            'detail_price_rows' => $detailPriceRows,
            'financial_note' => isset($fin['catalog_price_note']) ? (string) $fin['catalog_price_note'] : null,
            'seo_offer_candidate' => $seo,
            'validity' => $v['validity'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $detail
     * @return list<array{line: string, hint: ?string}>
     */
    private function buildDetailPriceRows(array $detail, ?Tenant $tenant): array
    {
        $rows = [];
        foreach ($detail as $t) {
            $rows[] = $this->formatDetailPriceRow($t, $tenant);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array{line: string, hint: ?string}
     */
    private function formatDetailPriceRow(array $t, ?Tenant $tenant): array
    {
        $kind = TariffKind::tryFrom((string) ($t['kind'] ?? ''));
        $mode = ApplicabilityMode::tryFrom((string) ($t['applicability_mode'] ?? '')) ?? ApplicabilityMode::Always;
        $dayUnit = $t['catalog_day_unit'] instanceof TariffCatalogDayUnit
            ? $t['catalog_day_unit']
            : TariffCatalogDayUnit::fromProfile($t['catalog_day_unit'] ?? null);
        $hint = (string) ($t['catalog_public_hint'] ?? '');
        $label = trim((string) ($t['label'] ?? ''));
        $labelLed = self::labelWithOptionalParenthetical($label, $hint);
        $minor = $t['minor'] ?? null;
        $minDays = max(1, (int) ($t['min_days'] ?? 1));
        $maxDays = max($minDays, (int) ($t['max_days'] ?? $minDays));

        if ($kind === TariffKind::OnRequest) {
            $line = self::labelWithOptionalParenthetical($label, $hint);

            return ['line' => $line !== '' ? $line : 'По запросу', 'hint' => null];
        }
        if ($kind === TariffKind::Informational) {
            return ['line' => self::labelWithOptionalParenthetical($label, $hint), 'hint' => null];
        }
        if ($kind === null || $minor === null) {
            return ['line' => $labelLed !== '' ? $labelLed : trim($label), 'hint' => null];
        }

        $major = PricingMinorMoney::minorToMajor((int) $minor);
        $amount = $this->formatMajor($tenant, $major);

        if ($kind === TariffKind::FixedPerDay && $mode === ApplicabilityMode::DurationRangeDays) {
            $bucket = $dayUnit->rangeBucketWord();
            $suffix = $dayUnit->perUnitSuffix();
            $auto = "{$minDays}–{$maxDays} {$bucket} по {$amount} {$suffix}";
            if ($labelLed !== '') {
                return ['line' => $labelLed.' — '.$auto, 'hint' => null];
            }

            return ['line' => $auto, 'hint' => null];
        }

        if ($kind === TariffKind::FixedPerDay && $mode === ApplicabilityMode::DurationMinDays) {
            $bucket = $dayUnit->rangeBucketWord();
            $suffix = $dayUnit->perUnitSuffix();
            $auto = "от {$minDays} {$bucket} по {$amount} {$suffix}";
            if ($labelLed !== '') {
                return ['line' => $labelLed.' — '.$auto, 'hint' => null];
            }

            return ['line' => $auto, 'hint' => null];
        }

        if ($kind === TariffKind::FixedPerRental && $mode === ApplicabilityMode::DurationRangeDays) {
            $auto = "{$minDays}–{$maxDays} суток по {$amount} за период";
            if ($labelLed !== '') {
                return ['line' => $labelLed.' — '.$auto, 'hint' => null];
            }

            return ['line' => $auto, 'hint' => null];
        }

        $suffix = self::catalogUnitSuffix($kind, $kind === TariffKind::FixedPerDay ? $dayUnit : null);
        $line = $suffix !== '' ? $amount.' '.$suffix : $amount;

        if ($labelLed !== '') {
            return ['line' => $labelLed.': '.$line, 'hint' => null];
        }

        return ['line' => $line, 'hint' => null];
    }

    private static function labelWithOptionalParenthetical(string $label, string $hint): string
    {
        $label = trim($label);
        $hint = trim($hint);
        if ($label === '') {
            return $hint === '' ? '' : '('.$hint.')';
        }
        if ($hint === '') {
            return $label;
        }

        return $label.' ('.$hint.')';
    }

    private static function normalizeDetailTariffsLimit(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $n = (int) $raw;

        return $n >= 1 ? $n : null;
    }

    /**
     * @param  list<array<string, mixed>>  $detail
     * @return list<array<string, mixed>>
     */
    private static function applyDetailTariffsLimit(array $detail, ?int $limit): array
    {
        if ($limit === null) {
            return $detail;
        }

        return array_slice($detail, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $tariff
     */
    private function formatTariffCatalogLine(array $tariff, ?Tenant $tenant): string
    {
        $kind = TariffKind::tryFrom((string) ($tariff['kind'] ?? ''));
        $labelRaw = trim((string) ($tariff['label'] ?? ''));
        $hintRaw = trim((string) ($tariff['catalog_public_hint'] ?? ''));

        if ($kind === TariffKind::OnRequest) {
            $line = self::labelWithOptionalParenthetical($labelRaw, $hintRaw);

            return $line !== '' ? $line : 'По запросу';
        }
        if ($kind === TariffKind::Informational) {
            return self::labelWithOptionalParenthetical($labelRaw, $hintRaw);
        }
        if ($kind === null || ! isset($tariff['amount_minor'])) {
            return self::labelWithOptionalParenthetical($labelRaw, $hintRaw);
        }
        $major = PricingMinorMoney::minorToMajor((int) $tariff['amount_minor']);
        $amount = $this->formatMajor($tenant, $major);
        $dayUnit = $kind === TariffKind::FixedPerDay
            ? TariffCatalogDayUnit::fromProfile($tariff['catalog_day_unit'] ?? null)
            : null;
        $suffix = self::catalogUnitSuffix($kind, $dayUnit);
        $led = self::labelWithOptionalParenthetical($labelRaw, $hintRaw);
        $tail = trim($amount.' '.$suffix);

        return $led !== '' ? $led.': '.$tail : $tail;
    }

    /**
     * Human unit phrase after formatted amount (catalog / cards), lowercase.
     */
    public static function catalogUnitSuffix(?TariffKind $kind, ?TariffCatalogDayUnit $dayUnit = null): string
    {
        if ($kind === null) {
            return '';
        }

        return match ($kind) {
            TariffKind::FixedPerDay => ($dayUnit ?? TariffCatalogDayUnit::FullDay)->perUnitSuffix(),
            TariffKind::FixedPerRental => 'за период',
            TariffKind::FixedPerHourBlock => 'за блок часов',
            TariffKind::OnRequest, TariffKind::Informational => '',
        };
    }

    /**
     * @return array{0: string, 1: string, 2: bool}
     */
    private function buildCardPriceParts(
        bool $invalid,
        bool $cardOnRequest,
        ?TariffKind $primaryKind,
        ?int $cardMinor,
        string $cardLabel,
        ?Tenant $tenant,
        ?TariffCatalogDayUnit $primaryDayUnit = null,
    ): array {
        if ($invalid) {
            return ['Стоимость уточняйте', '', false];
        }
        if ($cardOnRequest) {
            return ['По запросу', '', false];
        }
        if ($primaryKind === TariffKind::Informational) {
            $line = trim($cardLabel);

            return [$line !== '' ? $line : 'Условия на странице модели', '', false];
        }
        if ($cardMinor !== null && $primaryKind !== null && $cardMinor > 0) {
            $major = PricingMinorMoney::minorToMajor($cardMinor);
            $text = $this->formatMajor($tenant, $major);
            $suffix = self::catalogUnitSuffix(
                $primaryKind,
                $primaryKind === TariffKind::FixedPerDay ? $primaryDayUnit : null,
            );
            $from = $primaryKind === TariffKind::FixedPerDay;

            return [$text, $suffix, $from];
        }

        return ['', '', false];
    }

    private function formatMajor(?Tenant $tenant, int $major): string
    {
        if ($tenant !== null) {
            return tenant_money_format($major, MoneyBindingRegistry::MOTORCYCLE_PRICE_PER_DAY, $tenant);
        }

        return number_format($major, 0, ',', ' ').' ₽';
    }

    /**
     * @param  list<array<string, mixed>>  $tariffs
     * @return ?array<string, mixed>
     */
    private function findTariff(array $tariffs, string $id): ?array
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

    /**
     * @param  ?array<string, mixed>  $primary
     * @return ?array{minor: int, currency: string, label: string, price_descriptor: string}
     */
    private function buildSeoOfferCandidate(?array $primary, string $currency, PricingProfileValidity $validity): ?array
    {
        if ($validity === PricingProfileValidity::Invalid) {
            return null;
        }
        if (! is_array($primary)) {
            return null;
        }
        $kind = TariffKind::tryFrom((string) ($primary['kind'] ?? ''));
        if ($kind === null || $kind === TariffKind::OnRequest || $kind === TariffKind::Informational) {
            return null;
        }
        $app = is_array($primary['applicability'] ?? null) ? $primary['applicability'] : [];
        if (($app['mode'] ?? '') === ApplicabilityMode::ManualOnly->value) {
            return null;
        }
        if (! isset($primary['amount_minor'])) {
            return null;
        }
        $minor = (int) $primary['amount_minor'];
        if ($minor <= 0) {
            return null;
        }

        $dayUnit = $kind === TariffKind::FixedPerDay
            ? TariffCatalogDayUnit::fromProfile($primary['catalog_day_unit'] ?? null)
            : null;

        return [
            'minor' => $minor,
            'currency' => $currency,
            'label' => (string) ($primary['label'] ?? ''),
            'price_descriptor' => self::catalogUnitSuffix($kind, $dayUnit),
        ];
    }
}
