<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Requests\StorePublicBookingCheckoutRequest;
use App\Models\Tenant;
use App\Models\TenantBookingConsentItem;
use App\Models\TenantSetting;
use App\Services\CurrentTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

class StorePublicBookingCheckoutRequestRulesTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    private function makeFormRequest(): StorePublicBookingCheckoutRequest
    {
        $base = Request::create('https://motolevins.example.test/booking/checkout', 'POST', []);
        $req = StorePublicBookingCheckoutRequest::createFrom($base);
        $req->setContainer($this->app);
        $req->setRedirector($this->app['redirect']);

        return $req;
    }

    public function test_rules_merge_legacy_consents_when_dynamic_consents_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('chkoutlegacy');
        TenantSetting::setForTenant((int) $tenant->id, 'booking.legal_consents_required', false);
        app(CurrentTenantManager::class)->setTenant(Tenant::query()->findOrFail($tenant->id));

        $rules = $this->makeFormRequest()->rules();

        $this->assertArrayHasKey('agree_to_terms', $rules);
        $this->assertArrayHasKey('agree_to_privacy', $rules);
        $this->assertArrayNotHasKey('consent_accepted', $rules);
    }

    public function test_rules_use_dynamic_consents_when_enabled_and_items_exist(): void
    {
        $tenant = $this->createTenantWithActiveDomain('chkoutdyn');
        $tid = (int) $tenant->id;
        TenantSetting::setForTenant($tid, 'booking.legal_consents_required', true);
        TenantBookingConsentItem::query()->create([
            'tenant_id' => $tid,
            'code' => 'test_required',
            'label' => 'Я согласен с условиями',
            'link_text' => null,
            'link_url' => null,
            'is_required' => true,
            'is_enabled' => true,
            'sort_order' => 0,
            'meta_json' => null,
        ]);
        app(CurrentTenantManager::class)->setTenant(Tenant::query()->findOrFail($tid));

        $rules = $this->makeFormRequest()->rules();

        $this->assertArrayNotHasKey('agree_to_terms', $rules);
        $this->assertArrayNotHasKey('agree_to_privacy', $rules);
        $this->assertArrayHasKey('consent_accepted', $rules);
    }
}
