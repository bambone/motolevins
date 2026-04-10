<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\BookableService;
use App\Models\CalendarConnection;
use App\Models\CalendarSubscription;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Scheduling\Enums\CalendarAccessMode;
use App\Scheduling\Enums\CalendarProviderType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\WriteCalendarResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\SchedulingTestScenarios;
use Tests\TestCase;

/**
 * Platform panel: только platform-scope услуги; без утечки tenant-строк; platform default write resolver.
 */
final class PlatformSchedulingBookableServiceAccessTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;
    use SchedulingTestScenarios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function getWithHost(string $host, string $path): TestResponse
    {
        $path = str_starts_with($path, '/') ? $path : '/'.$path;

        return $this->call('GET', 'http://'.$host.$path);
    }

    private function platformHost(): string
    {
        return trim((string) config('app.platform_host'));
    }

    public function test_platform_list_shows_only_platform_scope_services(): void
    {
        $tenant = $this->createTenantWithActiveDomain('plat_svc_t');

        $tenantTitle = 'TENANT-ONLY-'.uniqid();
        $this->schedulingCreateBookableService($tenant, ['title' => $tenantTitle, 'slug' => 't-'.uniqid()]);

        $platformTitle = 'PLATFORM-ONLY-'.uniqid();
        BookableService::query()->create([
            'scheduling_scope' => SchedulingScope::Platform,
            'tenant_id' => null,
            'slug' => 'plat-'.uniqid(),
            'title' => $platformTitle,
            'duration_minutes' => 30,
            'slot_step_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'min_booking_notice_minutes' => 0,
            'max_booking_horizon_days' => 30,
            'requires_confirmation' => false,
            'is_active' => true,
            'sort_weight' => 0,
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $response = $this->actingAs($user)->getWithHost($this->platformHost(), '/platform-bookable-services');

        $response->assertOk();
        $response->assertSee($platformTitle, false);
        $response->assertDontSee($tenantTitle, false);
    }

    public function test_platform_create_page_opens_without_server_error(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('platform_owner');

        $response = $this->actingAs($user)->getWithHost($this->platformHost(), '/platform-bookable-services/create');

        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertOk();
    }

    public function test_platform_default_write_subscription_resolver_without_tenant(): void
    {
        $connTenant = $this->createTenantWithActiveDomain('plat_wr_conn');

        PlatformSetting::query()->where('key', 'scheduling.default_write_calendar_subscription_id')->delete();
        Cache::forget('platform_settings.scheduling.default_write_calendar_subscription_id');

        $conn = CalendarConnection::query()->create([
            'scheduling_scope' => SchedulingScope::Tenant,
            'tenant_id' => $connTenant->id,
            'provider' => CalendarProviderType::Google,
            'access_mode' => CalendarAccessMode::Oauth,
            'display_name' => 'WR',
            'is_active' => true,
        ]);
        $sub = CalendarSubscription::query()->create([
            'calendar_connection_id' => $conn->id,
            'external_calendar_id' => 'primary',
            'use_for_busy' => true,
            'use_for_write' => true,
            'is_active' => true,
        ]);

        PlatformSetting::set('scheduling.default_write_calendar_subscription_id', $sub->id, 'integer');

        $resolved = app(WriteCalendarResolver::class)->resolveSubscription(null, null, null, null);

        $this->assertNotNull($resolved);
        $this->assertSame($sub->id, $resolved->id);
    }
}
