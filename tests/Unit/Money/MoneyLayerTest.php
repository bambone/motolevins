<?php

namespace Tests\Unit\Money;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Money\MoneyBindingRegistry;
use App\Money\MoneyFormatter;
use App\Money\MoneyParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyLayerTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantRub(): Tenant
    {
        $tenant = Tenant::query()->create([
            'name' => 'Money Test',
            'slug' => 'money-test-'.uniqid(),
            'status' => 'active',
            'currency' => 'RUB',
        ]);
        TenantSetting::setForTenant($tenant->id, 'money.base_currency_code', 'RUB');
        TenantSetting::setForTenant($tenant->id, 'money.fraction_display_mode', 'never');
        TenantSetting::setForTenant($tenant->id, 'money.display_scale_exponent', 0);

        return $tenant->fresh();
    }

    public function test_formatter_formats_motorcycle_major_integer(): void
    {
        $tenant = $this->makeTenantRub();
        $formatter = app(MoneyFormatter::class);
        $view = $formatter->formatStorageInt(2700, MoneyBindingRegistry::MOTORCYCLE_PRICE_PER_DAY, $tenant);

        $this->assertSame('2700', $view->logicalMajorAmount);
        $this->assertStringContainsString('2', $view->formatted);
        $this->assertStringContainsString('700', $view->formatted);
    }

    public function test_parser_round_trip_motorcycle_price(): void
    {
        $tenant = $this->makeTenantRub();
        $parser = app(MoneyParser::class);
        $formatter = app(MoneyFormatter::class);

        $storage = $parser->parseToStorageInt('2700', MoneyBindingRegistry::MOTORCYCLE_PRICE_PER_DAY, $tenant);
        $this->assertSame(2700, $storage);

        $out = $formatter->formatStorageInt($storage, MoneyBindingRegistry::MOTORCYCLE_PRICE_PER_DAY, $tenant);
        $this->assertSame('2700', $out->logicalMajorAmount);
    }

    public function test_formatter_minor_units_for_service_program(): void
    {
        $tenant = $this->makeTenantRub();
        $formatter = app(MoneyFormatter::class);
        $view = $formatter->formatStorageInt(2700 * 100, MoneyBindingRegistry::TENANT_SERVICE_PROGRAM_PRICE_AMOUNT, $tenant);

        $this->assertSame('2700.00', $view->logicalMajorAmount);
    }

    public function test_strict_binding_unknown_key_throws_in_tests(): void
    {
        config(['money.strict_bindings' => true]);
        $tenant = $this->makeTenantRub();
        $formatter = app(MoneyFormatter::class);

        $this->expectException(\App\Money\Exceptions\UnknownMoneyBindingException::class);
        $formatter->formatStorageInt(1, 'no.such.binding.key', $tenant);
    }
}
