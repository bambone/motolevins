<?php

declare(strict_types=1);

namespace Tests\Unit\MotorcyclePricing;

use App\MotorcyclePricing\ApplicabilityMode;
use App\MotorcyclePricing\MotorcyclePricingProfileFormHydrator;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\TariffKind;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MotorcyclePricingProfileFormHydratorTest extends TestCase
{
    public function test_profile_to_flat_form_orders_tariffs_by_sort_order_priority_then_original_index(): void
    {
        $idLateInJson = (string) Str::uuid();
        $idEarlyInJson = (string) Str::uuid();

        $profile = [
            'currency' => 'RUB',
            'tariffs' => [
                $this->profileTariff($idLateInJson, sortOrder: 20, priority: 100),
                $this->profileTariff($idEarlyInJson, sortOrder: 10, priority: 200),
            ],
            'display' => [
                'card_primary_tariff_id' => $idEarlyInJson,
                'card_secondary_mode' => 'none',
                'card_secondary_text' => '',
                'card_secondary_tariff_id' => null,
            ],
            'financial_terms' => [],
        ];

        $flat = MotorcyclePricingProfileFormHydrator::profileToFlatForm($profile);
        $rows = $flat['tariffs'];
        $this->assertCount(2, $rows);
        $this->assertSame($idEarlyInJson, (string) ($rows[0]['id'] ?? ''));
        $this->assertSame($idLateInJson, (string) ($rows[1]['id'] ?? ''));
        $this->assertArrayNotHasKey('priority', $rows[0]);
        $this->assertArrayNotHasKey('sort_order', $rows[0]);
    }

    public function test_flat_form_to_profile_renumbers_order_compactly_after_middle_row_removed(): void
    {
        $id1 = (string) Str::uuid();
        $id3 = (string) Str::uuid();

        $flat = [
            'currency' => 'RUB',
            'tariffs' => [
                array_merge(MotorcyclePricingProfileFormHydrator::defaultTariffRow(), [
                    'id' => $id1,
                    'label' => 'First',
                    'amount_major' => 1000,
                ]),
                array_merge(MotorcyclePricingProfileFormHydrator::defaultTariffRow(), [
                    'id' => $id3,
                    'label' => 'Third',
                    'amount_major' => 3000,
                ]),
            ],
            'card_primary_tariff_id' => $id1,
            'card_secondary_mode' => 'none',
            'card_secondary_text' => '',
            'card_secondary_tariff_id' => '',
            'detail_tariffs_limit' => null,
            'deposit_amount' => null,
            'prepayment_amount' => null,
            'catalog_price_note' => '',
        ];

        $profile = MotorcyclePricingProfileFormHydrator::flatFormToProfile($flat);
        $tariffs = $profile['tariffs'];
        $this->assertCount(2, $tariffs);
        $this->assertSame(MotorcyclePricingSchema::orderValueForIndex(0), (int) ($tariffs[0]['priority'] ?? 0));
        $this->assertSame(MotorcyclePricingSchema::orderValueForIndex(0), (int) ($tariffs[0]['sort_order'] ?? 0));
        $this->assertSame(MotorcyclePricingSchema::orderValueForIndex(1), (int) ($tariffs[1]['priority'] ?? 0));
        $this->assertSame(MotorcyclePricingSchema::orderValueForIndex(1), (int) ($tariffs[1]['sort_order'] ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    private function profileTariff(string $id, int $sortOrder, int $priority): array
    {
        return [
            'id' => $id,
            'label' => 'T '.$id,
            'kind' => TariffKind::FixedPerDay->value,
            'amount_minor' => 10_000,
            'applicability' => ['mode' => ApplicabilityMode::Always->value],
            'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
            'sort_order' => $sortOrder,
            'priority' => $priority,
        ];
    }
}
