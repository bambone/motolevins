<?php

namespace Tests\Feature\Tenant;

use App\Jobs\SendLeadTelegramNotification;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class LeadStoreLegacyTelegramParallelTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_legacy_parallel_off_does_not_dispatch_telegram_job(): void
    {
        config(['notification_center.legacy_telegram_parallel' => false]);
        Bus::fake();

        $tenant = $this->createTenantWithActiveDomain('legoff');
        $host = $this->tenancyHostForSlug('legoff');

        $this->postJson('http://'.$host.'/api/leads', [
            'name' => 'Tester',
            'phone' => '+79991112201',
            'email' => null,
            'comment' => null,
        ])->assertOk();

        Bus::assertNotDispatched(SendLeadTelegramNotification::class);
    }

    public function test_legacy_parallel_on_dispatches_telegram_job(): void
    {
        config(['notification_center.legacy_telegram_parallel' => true]);
        Bus::fake();

        $tenant = $this->createTenantWithActiveDomain('legon');
        $host = $this->tenancyHostForSlug('legon');

        $this->postJson('http://'.$host.'/api/leads', [
            'name' => 'Tester',
            'phone' => '+79991112202',
            'email' => null,
            'comment' => null,
        ])->assertOk();

        Bus::assertDispatched(SendLeadTelegramNotification::class);
    }
}
