<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class TenantPublicReviewsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_reviews_page_and_api_return_only_published_for_tenant(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubrev');
        $host = $this->tenancyHostForSlug('pubrev');

        Review::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Visible',
            'text' => 'Good',
            'rating' => 5,
            'status' => 'published',
            'sort_order' => 0,
        ]);
        Review::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Draft',
            'text' => 'Hidden',
            'rating' => 5,
            'status' => 'draft',
            'sort_order' => 1,
        ]);

        $html = $this->call('GET', 'http://'.$host.'/reviews');
        $html->assertOk();
        $html->assertSee('Visible', false);
        $html->assertDontSee('Draft', false);

        $json = $this->call('GET', 'http://'.$host.'/api/tenant/reviews');
        $json->assertOk();
        $json->assertJsonPath('data.0.name', 'Visible');
        $json->assertJsonCount(1, 'data');
    }
}
