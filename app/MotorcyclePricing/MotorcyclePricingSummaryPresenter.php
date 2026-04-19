<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Money\MoneyBindingRegistry;

/**
 * View-layer DTO builder: card line, detail list, SEO candidate (no raw profile leakage).
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
            $cardLabel = (string) ($primary['label'] ?? '');
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

        [$cardPriceText, $cardPriceSuffix, $cardShowLeadingFrom] = $this->buildCardPriceParts(
            $invalid,
            $cardOnRequest,
            $primaryKind,
            $cardMinor,
            $cardLabel,
            $tenant,
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
            $detail[] = [
                'id' => (string) ($t['id'] ?? ''),
                'label' => (string) ($t['label'] ?? ''),
                'kind' => (string) ($t['kind'] ?? ''),
                'minor' => isset($t['amount_minor']) ? (int) $t['amount_minor'] : null,
            ];
        }

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
     * @param  list<array{label: string, minor: ?int, kind: string, id: string}>  $detail
     * @return list<array{line: string, hint: ?string}>
     */
    private function buildDetailPriceRows(array $detail, ?Tenant $tenant): array
    {
        $rows = [];
        foreach ($detail as $t) {
            $kind = TariffKind::tryFrom((string) ($t['kind'] ?? ''));
            if ($kind === TariffKind::OnRequest) {
                $label = trim((string) $t['label']);
                $rows[] = ['line' => $label !== '' ? $label : 'По запросу', 'hint' => null];

                continue;
            }
            if ($kind === TariffKind::Informational) {
                $rows[] = ['line' => trim((string) $t['label']), 'hint' => null];

                continue;
            }
            if ($t['minor'] !== null && $kind !== null) {
                $major = PricingMinorMoney::minorToMajor((int) $t['minor']);
                $amount = $this->formatMajor($tenant, $major);
                $suffix = self::catalogUnitSuffix($kind);
                $line = $suffix !== '' ? $amount.' '.$suffix : $amount;
                $label = trim((string) $t['label']);
                $rows[] = [
                    'line' => $label !== '' ? $label.': '.$line : $line,
                    'hint' => null,
                ];

                continue;
            }
            $rows[] = ['line' => trim((string) $t['label']), 'hint' => null];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $tariff
     */
    private function formatTariffCatalogLine(array $tariff, ?Tenant $tenant): string
    {
        $kind = TariffKind::tryFrom((string) ($tariff['kind'] ?? ''));
        if ($kind === TariffKind::OnRequest) {
            $label = trim((string) ($tariff['label'] ?? ''));

            return $label !== '' ? $label : 'По запросу';
        }
        if ($kind === TariffKind::Informational) {
            return trim((string) ($tariff['label'] ?? ''));
        }
        if ($kind === null || ! isset($tariff['amount_minor'])) {
            return trim((string) ($tariff['label'] ?? ''));
        }
        $major = PricingMinorMoney::minorToMajor((int) $tariff['amount_minor']);
        $amount = $this->formatMajor($tenant, $major);
        $suffix = self::catalogUnitSuffix($kind);

        return trim($amount.' '.$suffix);
    }

    /**
     * Human unit phrase after formatted amount (catalog / cards), lowercase.
     */
    public static function catalogUnitSuffix(?TariffKind $kind): string
    {
        if ($kind === null) {
            return '';
        }

        return match ($kind) {
            TariffKind::FixedPerDay => 'за сутки',
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
            $suffix = self::catalogUnitSuffix($primaryKind);
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

        return [
            'minor' => $minor,
            'currency' => $currency,
            'label' => (string) ($primary['label'] ?? ''),
            'price_descriptor' => self::catalogUnitSuffix($kind),
        ];
    }
}
