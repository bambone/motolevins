<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Enums\MotorcycleLocationMode;
use App\Filament\Tenant\Resources\MotorcycleResource\Form\MotorcycleFormFieldKit;
use App\MotorcyclePricing\MotorcyclePricingProfileFormHydrator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class MotorcycleFormFieldKitTest extends TestCase
{
    public function test_normalize_fleet_location_clears_per_unit_when_fleet_off(): void
    {
        $data = MotorcycleFormFieldKit::normalizeFleetLocationFormState([
            'uses_fleet_units' => false,
            'location_mode' => MotorcycleLocationMode::PerUnit->value,
            'tenant_location_ids' => [1, 2],
        ]);

        $this->assertFalse($data['uses_fleet_units']);
        $this->assertSame(MotorcycleLocationMode::Everywhere->value, $data['location_mode']);
        $this->assertSame([], $data['tenant_location_ids']);
    }

    public function test_normalize_fleet_location_keeps_tenant_locations_when_selected(): void
    {
        $data = MotorcycleFormFieldKit::normalizeFleetLocationFormState([
            'uses_fleet_units' => true,
            'location_mode' => MotorcycleLocationMode::Selected->value,
            'tenant_location_ids' => [5, 7],
        ]);

        $this->assertSame([5, 7], $data['tenant_location_ids']);
    }

    public function test_merge_pricing_profile_blocks_stale_primary_tariff_reference(): void
    {
        $this->expectException(ValidationException::class);

        $keepId = (string) Str::uuid();
        $staleId = (string) Str::uuid();
        $row = array_merge(MotorcyclePricingProfileFormHydrator::defaultTariffRow(), [
            'id' => $keepId,
            'amount_major' => 1500,
            'label' => 'День',
        ]);

        MotorcycleFormFieldKit::mergePricingProfileIntoMotorcycleData([
            'pricing_currency' => 'RUB',
            'pricing_tariffs' => [$row],
            'pricing_card_primary_tariff_id' => $staleId,
            'pricing_card_secondary_mode' => 'none',
            'pricing_card_secondary_text' => '',
            'pricing_card_secondary_tariff_id' => '',
            'pricing_detail_tariffs_limit' => null,
            'pricing_deposit_amount' => null,
            'pricing_prepayment_amount' => null,
            'pricing_catalog_price_note' => '',
        ]);
    }

    public function test_merge_pricing_profile_sets_primary_when_single_tariff_and_empty(): void
    {
        $keepId = (string) Str::uuid();
        $row = array_merge(MotorcyclePricingProfileFormHydrator::defaultTariffRow(), [
            'id' => $keepId,
            'amount_major' => 1500,
            'label' => 'День',
        ]);

        $merged = MotorcycleFormFieldKit::mergePricingProfileIntoMotorcycleData([
            'pricing_currency' => 'RUB',
            'pricing_tariffs' => [$row],
            'pricing_card_primary_tariff_id' => '',
            'pricing_card_secondary_mode' => 'none',
            'pricing_card_secondary_text' => '',
            'pricing_card_secondary_tariff_id' => '',
            'pricing_detail_tariffs_limit' => null,
            'pricing_deposit_amount' => null,
            'pricing_prepayment_amount' => null,
            'pricing_catalog_price_note' => '',
        ]);

        $profile = $merged['pricing_profile_json'];
        $this->assertIsArray($profile);
        $display = $profile['display'] ?? [];
        $this->assertSame($keepId, (string) ($display['card_primary_tariff_id'] ?? ''));
    }
}
