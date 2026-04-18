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
use App\TenantSiteSetup\SetupItemRegistry;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class BookingNotificationsBriefingStateRegressionTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_completion_becomes_incomplete_when_wizard_artifacts_removed(): void
    {
        $tenant = $this->createTenantWithActiveDomain('bn_reg', ['theme_key' => 'expert_auto', 'scheduling_module_enabled' => true]);
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
        $data['dest_email'] = 'reg@example.test';
        $data['events_enabled'] = ['crm_request.created'];

        app(BookingNotificationsBriefingApplier::class)->apply($tenant, $user, $data);

        $def = SetupItemRegistry::definitions()['setup.booking_notifications_brief'];
        $eval = app(SetupCompletionEvaluator::class);
        $this->assertTrue($eval->isComplete($tenant, $def));

        BookingSettingsPreset::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', BookingNotificationsBriefingWizardMarkers::PRESET_NAME)
            ->delete();
        NotificationDestination::query()->where('tenant_id', $tenant->id)->delete();
        NotificationSubscription::query()->where('tenant_id', $tenant->id)->delete();

        $this->assertFalse($eval->isComplete($tenant, $def));
    }
}
