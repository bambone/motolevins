<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Models\CalendarConnection;
use App\Scheduling\Calendar\NullCalendarProviderAdapter;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\SchedulingScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class NullCalendarProviderAdapterTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_supports_is_false_and_list_calendars_empty(): void
    {
        $tenant = $this->createTenantWithActiveDomain('null_adapt');
        $conn = CalendarConnection::query()->make([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $tenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'N',
            'is_active' => true,
        ]);

        $adapter = new NullCalendarProviderAdapter;
        $this->assertFalse($adapter->supports($conn));
        $this->assertSame([], $adapter->listCalendars($conn));
        $this->assertFalse($adapter->supportsWebhooks());
        $adapter->syncBusy($conn, Carbon::now('UTC'), Carbon::now('UTC')->addHour());
    }
}
