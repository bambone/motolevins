<?php

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\Motorcycle;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\CurrentTenant;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

/**
 * Регрессия: колонка превью не должна сжиматься до w-px (визуальный сдвиг текста относительно заголовков).
 * Проверяем порядок ячеек в строке: превью → дата → модель+имя → телефон → …
 */
class ListLeadsTableLayoutTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_list_leads_row_has_expected_td_count_and_column_order(): void
    {
        $tenant = $this->createTenantWithActiveDomain('lead_tbl');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $motorcycle = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'GRID TEST BIKE',
            'slug' => 'grid-test-bike',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'КлиентДляСетки',
            'phone' => '+79991234567',
            'motorcycle_id' => $motorcycle->id,
            'rental_date_from' => '2026-04-01',
            'rental_date_to' => '2026-04-03',
            'source' => 'booking_form',
            'status' => 'new',
        ]);

        Lead::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'ДлинныйТел',
            'phone' => '+79999999999999999999',
            'motorcycle_id' => $motorcycle->id,
            'rental_date_from' => '2026-05-01',
            'rental_date_to' => '2026-05-02',
            'source' => 'booking_form',
            'status' => 'in_progress',
        ]);

        $this->bindTenantFilamentContext($tenant);

        $html = Livewire::actingAs($user)
            ->test(ListLeads::class)
            ->assertSuccessful()
            ->html();

        $this->assertStringContainsString('fi-ta-cell-motorcycle-thumb', $html);
        $this->assertStringContainsString('fi-lead-list-table', $html);
        $this->assertStringContainsString('fi-lead-col-thumb', $html);
        $this->assertStringContainsString('Дата с', $html);
        $this->assertStringNotContainsString('w-px max-w-14', $html);

        $rowInner = $this->firstFiTaRowInnerHtmlContaining($html, 'КлиентДляСетки');
        $this->assertNotNull($rowInner, 'Expected a fi-ta-row containing the lead client name');

        preg_match_all('#<td\b[^>]*>(.*?)</td>#s', $rowInner, $m);
        $this->assertCount(10, $m[0], 'Expected 9 data columns + record actions column');

        $strip = static fn (string $h): string => trim(html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $this->assertMatchesRegularExpression('/<img\b/i', $m[1][0], 'First cell should contain preview image HTML');
        $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}/', $strip($m[1][1]), 'Second cell: received datetime');
        $this->assertStringContainsString('GRID TEST BIKE', $strip($m[1][2]));
        $this->assertStringContainsString('КлиентДляСетки', $strip($m[1][2]));
        $this->assertStringContainsString('+7 999 123-45-67', $strip($m[1][3]));

        $rowLongPhone = $this->firstFiTaRowInnerHtmlContaining($html, 'ДлинныйТел');
        $this->assertNotNull($rowLongPhone);
        preg_match_all('#<td\b[^>]*>(.*?)</td>#s', $rowLongPhone, $m2);
        $this->assertCount(10, $m2[0], 'Long phone row must keep same column count');
        $this->assertStringContainsString('+7 999 999-99-99', $strip($m2[1][3]));
        $this->assertStringContainsString('999 999 999', $strip($m2[1][3]));
        $this->assertStringNotContainsString('…', $strip($m2[1][3]));
    }

    private function bindTenantFilamentContext(Tenant $tenant): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
    }

    private function firstFiTaRowInnerHtmlContaining(string $html, string $needle): ?string
    {
        preg_match_all('/<tr\b[^>]*\bclass="[^"]*\bfi-ta-row\b[^"]*"[^>]*>(.*?)<\/tr>/s', $html, $all);
        foreach ($all[1] as $inner) {
            if (str_contains($inner, $needle)) {
                return $inner;
            }
        }

        return null;
    }
}
