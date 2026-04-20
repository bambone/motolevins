<?php

declare(strict_types=1);

namespace Tests\Unit\MotorcyclePricing;

use App\Models\Motorcycle;
use App\MotorcyclePricing\ApplicabilityMode;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\MotorcyclePricingSummaryPresenter;
use App\MotorcyclePricing\TariffCatalogDayUnit;
use App\MotorcyclePricing\TariffKind;
use Tests\TestCase;

final class MotorcyclePricingSummaryPresenterTest extends TestCase
{
    public function test_detail_uses_short_day_and_parenthetical_hint(): void
    {
        $present = $this->presentWithProfile([
            [
                'id' => 't1',
                'label' => 'День',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => 11_000_00,
                'applicability' => ['mode' => ApplicabilityMode::Always->value],
                'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
                'priority' => 10,
                'sort_order' => 10,
                'catalog_day_unit' => TariffCatalogDayUnit::ShortDay->value,
                'catalog_public_hint' => '10 часов',
            ],
        ], 't1');

        $line = (string) ($present['detail_price_rows'][0]['line'] ?? '');
        $this->assertStringContainsString('День (10 часов)', $line);
        $this->assertStringContainsString('за день', $line);
    }

    public function test_detail_duration_range_full_day_bucket(): void
    {
        $present = $this->presentWithProfile([
            [
                'id' => 't1',
                'label' => '',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => 12_000_00,
                'applicability' => [
                    'mode' => ApplicabilityMode::DurationRangeDays->value,
                    'min_days' => 1,
                    'max_days' => 3,
                ],
                'visibility' => ['show_on_card' => false, 'show_on_detail' => true, 'show_in_quote' => true],
                'priority' => 10,
                'sort_order' => 10,
            ],
        ], 't1');

        $line = (string) ($present['detail_price_rows'][0]['line'] ?? '');
        $this->assertStringContainsString('1–3 суток', $line);
        $this->assertStringContainsString('за сутки', $line);
    }

    public function test_card_suffix_uses_short_day_for_primary(): void
    {
        $present = $this->presentWithProfile([
            [
                'id' => 't1',
                'label' => 'День',
                'kind' => TariffKind::FixedPerDay->value,
                'amount_minor' => 11_000_00,
                'applicability' => ['mode' => ApplicabilityMode::Always->value],
                'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
                'priority' => 10,
                'sort_order' => 10,
                'catalog_day_unit' => TariffCatalogDayUnit::ShortDay->value,
            ],
        ], 't1');

        $this->assertSame('за день', (string) ($present['card_price_suffix'] ?? ''));
    }

    public function test_detail_tariffs_limit_applies_to_rows_and_detail_tariffs(): void
    {
        $baseTariff = static fn (string $id, int $prio): array => [
            'id' => $id,
            'label' => 'Тариф '.$id,
            'kind' => TariffKind::FixedPerDay->value,
            'amount_minor' => 1000_00,
            'applicability' => ['mode' => ApplicabilityMode::Always->value],
            'visibility' => ['show_on_card' => false, 'show_on_detail' => true, 'show_in_quote' => true],
            'priority' => $prio,
            'sort_order' => $prio,
        ];

        $present = $this->presentWithProfile(
            [$baseTariff('t1', 10), $baseTariff('t2', 20), $baseTariff('t3', 30)],
            't1',
            2,
        );

        $this->assertCount(2, $present['detail_price_rows']);
        $this->assertCount(2, $present['detail_tariffs']);
        $this->assertSame('t1', (string) ($present['detail_tariffs'][0]['id'] ?? ''));
        $this->assertSame('t2', (string) ($present['detail_tariffs'][1]['id'] ?? ''));
    }

    public function test_secondary_informational_line_includes_catalog_public_hint(): void
    {
        $present = $this->presentWithProfile(
            [
                [
                    'id' => 't1',
                    'label' => 'Сутки',
                    'kind' => TariffKind::FixedPerDay->value,
                    'amount_minor' => 10_000_00,
                    'applicability' => ['mode' => ApplicabilityMode::Always->value],
                    'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
                    'priority' => 10,
                    'sort_order' => 10,
                ],
                [
                    'id' => 't2',
                    'label' => 'Дольше',
                    'kind' => TariffKind::Informational->value,
                    'applicability' => ['mode' => ApplicabilityMode::Always->value],
                    'visibility' => ['show_on_card' => false, 'show_on_detail' => true, 'show_in_quote' => true],
                    'priority' => 20,
                    'sort_order' => 20,
                    'catalog_public_hint' => 'цена договорная',
                ],
            ],
            't1',
            null,
            [
                'card_secondary_mode' => 'secondary_tariff',
                'card_secondary_tariff_id' => 't2',
            ],
        );

        $secondary = (string) ($present['card_secondary_text'] ?? '');
        $this->assertStringContainsString('Дольше', $secondary);
        $this->assertStringContainsString('цена договорная', $secondary);
        $this->assertStringContainsString('(', $secondary);
    }

    /**
     * @param  list<array<string, mixed>>  $tariffs
     * @param  array<string, mixed>  $displayOverrides
     * @return array<string, mixed>
     */
    private function presentWithProfile(
        array $tariffs,
        string $primaryId,
        ?int $detailTariffsLimit = null,
        array $displayOverrides = [],
    ): array {
        $display = array_merge([
            'card_primary_tariff_id' => $primaryId,
            'card_secondary_mode' => 'none',
            'card_secondary_text' => '',
            'card_secondary_tariff_id' => null,
        ], $displayOverrides);
        if ($detailTariffsLimit !== null) {
            $display['detail_tariffs_limit'] = $detailTariffsLimit;
        }

        $profile = [
            'schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
            'currency' => 'RUB',
            'tariffs' => $tariffs,
            'display' => $display,
            'financial_terms' => [],
        ];

        $m = new Motorcycle([
            'pricing_profile_json' => $profile,
            'pricing_profile_schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
        ]);

        return app(MotorcyclePricingSummaryPresenter::class)->present($m, null);
    }
}
