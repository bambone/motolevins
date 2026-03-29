<?php

namespace Tests\Feature\CRM;

use App\Filament\Shared\CRM\CrmSharedFilters;
use App\Filament\Tenant\Resources\CrmRequestResource;
use App\Models\CrmRequest;
use App\Tenant\CurrentTenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class CrmSharedFiltersMetadataTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_request_type_filter_options_respect_tenant_resource_scope(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('tmeta_a');
        $tenantB = $this->createTenantWithActiveDomain('tmeta_b');

        $this->makeCrmRequest($tenantA->id, ['request_type' => 'type_visible_only_for_a']);
        $this->makeCrmRequest($tenantB->id, ['request_type' => 'type_visible_only_for_b']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $domain = $tenantA->domains()->where('is_primary', true)->first();
        $this->app->instance(CurrentTenant::class, new CurrentTenant($tenantA, $domain, false, $this->tenancyHostForSlug('tmeta_a')));

        $scopedOptions = CrmSharedFilters::requestTypeOptionsForScopedQuery(CrmRequestResource::getEloquentQuery());

        $this->assertArrayHasKey('type_visible_only_for_a', $scopedOptions);
        $this->assertArrayNotHasKey('type_visible_only_for_b', $scopedOptions);

        $unscopedOptions = CrmSharedFilters::requestTypeOptionsForScopedQuery(CrmRequest::query());
        $this->assertArrayHasKey('type_visible_only_for_b', $unscopedOptions);

        Filament::setCurrentPanel(null);
    }

    public function test_request_type_filter_options_for_platform_resource_exclude_tenant_rows(): void
    {
        $tenantA = $this->createTenantWithActiveDomain('tmeta_p');

        $this->makeCrmRequest(null, ['request_type' => 'platform_only_type']);
        $this->makeCrmRequest($tenantA->id, ['request_type' => 'tenant_only_type_meta']);

        Filament::setCurrentPanel(Filament::getPanel('platform'));

        $scopedOptions = CrmSharedFilters::requestTypeOptionsForScopedQuery(
            \App\Filament\Platform\Resources\CrmRequestResource::getEloquentQuery()
        );

        $this->assertArrayHasKey('platform_only_type', $scopedOptions);
        $this->assertArrayNotHasKey('tenant_only_type_meta', $scopedOptions);

        Filament::setCurrentPanel(null);
    }
}
