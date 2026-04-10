<?php

declare(strict_types=1);

namespace Tests\Feature\Scheduling;

use App\Models\CalendarConnection;
use App\Scheduling\Calendar\CaldavCalendarProviderAdapter;
use App\Scheduling\Calendar\CalendarAdapterRegistry;
use App\Scheduling\Calendar\GoogleCalendarProviderAdapter;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class CalendarAdapterRegistryTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function connection(CalendarProviderType $provider): CalendarConnection
    {
        $tenant = $this->createTenantWithActiveDomain('cal_reg_'.$provider->value);

        return CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => $provider,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'X',
            'is_active' => true,
        ]);
    }

    public function test_google_maps_to_google_adapter(): void
    {
        $registry = app(CalendarAdapterRegistry::class);
        $adapter = $registry->forConnection($this->connection(CalendarProviderType::Google));
        $this->assertInstanceOf(GoogleCalendarProviderAdapter::class, $adapter);
    }

    public function test_yandex_maps_to_caldav_adapter(): void
    {
        $registry = app(CalendarAdapterRegistry::class);
        $adapter = $registry->forConnection($this->connection(CalendarProviderType::Yandex));
        $this->assertInstanceOf(CaldavCalendarProviderAdapter::class, $adapter);
    }

    public function test_mailru_maps_to_caldav_adapter(): void
    {
        $registry = app(CalendarAdapterRegistry::class);
        $adapter = $registry->forConnection($this->connection(CalendarProviderType::Mailru));
        $this->assertInstanceOf(CaldavCalendarProviderAdapter::class, $adapter);
    }
}
