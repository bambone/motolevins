<?php

namespace Tests\Feature\TenantSiteSetup;

use App\Models\BookingSettingsPreset;
use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\User;
use App\Tenant\CurrentTenant;
use App\TenantSiteSetup\BookingNotificationsBriefingApplier;
use App\TenantSiteSetup\BookingNotificationsBriefingWizardMarkers;
use App\TenantSiteSetup\BookingNotificationsQuestionnaireRepository;
use App\TenantSiteSetup\SetupCompletionEvaluator;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class BookingNotificationsBriefingApplierTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_apply_creates_preset_destinations_and_subscriptions(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bn_brief', ['theme_key' => 'expert_auto', 'scheduling_module_enabled' => true]);
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenant->domains()->where('is_primary', true)->first();
        $this->app->instance(
            CurrentTenant::class,
            new CurrentTenant($tenant, $domain, false, $this->tenancyHostForSlug((string) $tenant->slug))
        );
        $this->actingAs($user);

        $data = app(BookingNotificationsQuestionnaireRepository::class)->getMerged($tenant->id);
        $data['dest_email'] = 'ops@example.test';
        $data['events_enabled'] = ['crm_request.created'];

        $result = app(BookingNotificationsBriefingApplier::class)->apply($tenant, $user, $data);

        $this->assertSame(1, $result['destinations_created']);
        $this->assertSame(1, $result['subscriptions_created']);
        $this->assertNotNull($result['preset_id']);

        $this->assertTrue(
            BookingSettingsPreset::query()->where('tenant_id', $tenant->id)->where('name', BookingNotificationsBriefingWizardMarkers::PRESET_NAME)->exists()
        );
        $this->assertTrue(
            NotificationDestination::query()->where('tenant_id', $tenant->id)->where('name', BookingNotificationsBriefingWizardMarkers::DEST_EMAIL_NAME)->exists()
        );
        $this->assertTrue(
            NotificationSubscription::query()->where('tenant_id', $tenant->id)->where('event_key', 'crm_request.created')->exists()
        );

        $this->assertTrue(
            app(SetupCompletionEvaluator::class)->isComplete($tenant, \App\TenantSiteSetup\SetupItemRegistry::definitions()['setup.booking_notifications_brief'])
        );
    }
}
