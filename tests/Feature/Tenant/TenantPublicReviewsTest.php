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
            'body' => 'Good',
            'rating' => 5,
            'status' => 'published',
            'sort_order' => 0,
        ]);
        Review::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Draft',
            'body' => 'Hidden',
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
        $json->assertJsonPath('data.0.text', 'Good');
        $json->assertJsonPath('data.0.body', 'Good');
        $json->assertJsonCount(1, 'data');
    }

    public function test_reviews_page_shows_excerpt_and_read_more_button_for_long_text(): void
    {
        $tenant = $this->createTenantWithActiveDomain('pubrev-long');
        $host = $this->tenancyHostForSlug('pubrev-long');

        $tail = 'TAIL_UNIQUE_'.uniqid('', true);

        Review::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Long Author',
            'body' => str_repeat('слово ', 80).$tail,
            'rating' => 5,
            'status' => 'published',
            'sort_order' => 0,
        ]);

        $saved = Review::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Long Author')
            ->firstOrFail();

        // Карточка — выдержка без «хвоста» в конце длинного текста.
        $this->assertStringNotContainsString($tail, $saved->publicCardExcerpt());

        $response = $this->call('GET', 'http://'.$host.'/reviews');
        $response->assertOk();
        $response->assertSee('Читать полностью', false);
        $content = $response->getContent();
        $this->assertStringContainsString('data-review-body', (string) $content);
        $this->assertStringContainsString('data-review-toggle', (string) $content);
        $this->assertStringContainsString('aria-controls="review-body-'.$saved->id.'-0"', (string) $content);
        $this->assertStringContainsString('aria-expanded="false"', (string) $content);
        $response->assertSee($tail, false);
    }
}
