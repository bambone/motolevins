<?php

declare(strict_types=1);

namespace Tests\Feature\Product\CRM;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\MotorcyclePricing\ApplicabilityMode;
use App\MotorcyclePricing\MotorcyclePricingSchema;
use App\MotorcyclePricing\TariffKind;
use App\Product\CRM\TenantBookingFromCrmConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class TenantBookingFromCrmConverterPricingGateTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_does_not_materialize_confirmed_booking_when_quote_is_on_request(): void
    {
        $tenant = $this->createTenantWithActiveDomain('crm_gate_onreq');
        $tid = (string) Str::uuid();
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'OnReq Bike',
            'slug' => 'onreq-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 5000,
            'pricing_profile_json' => [
                'schema_version' => MotorcyclePricingSchema::PROFILE_VERSION,
                'currency' => 'RUB',
                'tariffs' => [[
                    'id' => $tid,
                    'label' => 'Индивидуально',
                    'kind' => TariffKind::OnRequest->value,
                    'note' => 'Звоните',
                    'applicability' => ['mode' => ApplicabilityMode::Always->value],
                    'visibility' => ['show_on_card' => true, 'show_on_detail' => true, 'show_in_quote' => true],
                    'priority' => 500,
                    'sort_order' => 10,
                ]],
                'display' => [
                    'card_primary_tariff_id' => $tid,
                    'card_secondary_mode' => 'none',
                    'card_secondary_text' => '',
                    'card_secondary_tariff_id' => null,
                ],
                'financial_terms' => [],
            ],
        ]);
        RentalUnit::query()->create([
            'tenant_id' => $tenant->id,
            'motorcycle_id' => $m->id,
            'status' => 'active',
        ]);

        $lead = Lead::query()->create([
            'tenant_id' => $tenant->id,
            'crm_request_id' => null,
            'name' => 'Клиент',
            'phone' => '+79991112233',
            'motorcycle_id' => $m->id,
            'rental_date_from' => '2026-08-01',
            'rental_date_to' => '2026-08-03',
            'source' => 'manual',
            'page_url' => '/',
            'status' => 'confirmed',
        ]);

        $ok = app(TenantBookingFromCrmConverter::class)->materializeConfirmedLeadBooking($lead);
        $this->assertFalse($ok);
        $this->assertSame(0, Booking::query()->where('lead_id', $lead->id)->count());
    }
}
