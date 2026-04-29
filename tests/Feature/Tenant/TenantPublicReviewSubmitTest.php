<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Review;
use App\Models\TenantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class TenantPublicReviewSubmitTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_submit_creates_pending_when_moderation_enabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revpend');
        $host = $this->tenancyHostForSlug('revpend');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '1');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.moderation_enabled', '1');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.form_show_rating', '1');

        $this->postJson('http://'.$host.'/api/tenant/reviews/submit', [
            'name' => 'Клиент Тест',
            'body' => str_repeat('а', 25),
            'consent' => '1',
            'rating' => '5',
            'page_url' => '/otzyvy',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('reviews', [
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'name' => 'Клиент Тест',
            'body' => str_repeat('а', 25),
            'rating' => 5,
        ]);
    }

    public function test_submit_with_enabled_rating_field_but_empty_rating_stores_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revrate-empty');
        $host = $this->tenancyHostForSlug('revrate-empty');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '1');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.moderation_enabled', '');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.form_show_rating', '1');

        $this->postJson('http://'.$host.'/api/tenant/reviews/submit', [
            'name' => 'Без оценки',
            'body' => str_repeat('ж', 22),
            'consent' => '1',
            'rating' => '',
            'page_url' => '/otzyvy',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'published');

        $this->assertDatabaseHas('reviews', [
            'tenant_id' => $tenant->id,
            'name' => 'Без оценки',
            'rating' => null,
        ]);
    }

    public function test_submit_creates_published_when_moderation_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revpub');
        $host = $this->tenancyHostForSlug('revpub');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '1');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.moderation_enabled', '');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.form_show_rating', '');

        $this->postJson('http://'.$host.'/api/tenant/reviews/submit', [
            'name' => 'Публичный',
            'body' => str_repeat('б', 22),
            'consent' => '1',
            'page_url' => '/otzyvy',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'published');

        $this->assertDatabaseHas('reviews', [
            'tenant_id' => $tenant->id,
            'status' => 'published',
            'name' => 'Публичный',
            'body' => str_repeat('б', 22),
            'rating' => null,
        ]);
    }

    public function test_pending_review_not_listed_in_public_api(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revapi');
        $host = $this->tenancyHostForSlug('revapi');

        Review::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Скрытый',
            'body' => str_repeat('в', 25),
            'rating' => 5,
            'status' => 'pending',
            'sort_order' => 0,
        ]);
        Review::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Видимый',
            'body' => 'Опубликован',
            'rating' => 5,
            'status' => 'published',
            'sort_order' => 0,
        ]);

        $this->call('GET', 'http://'.$host.'/api/tenant/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Видимый');
    }

    public function test_honeypot_does_not_create_review(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revhp');
        $host = $this->tenancyHostForSlug('revhp');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '1');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.form_show_rating', '');

        $this->postJson('http://'.$host.'/api/tenant/reviews/submit', [
            'name' => 'Спам',
            'body' => str_repeat('г', 25),
            'consent' => '1',
            'website' => 'http://spam.test',
            'page_url' => '/otzyvy',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('reviews', [
            'tenant_id' => $tenant->id,
            'name' => 'Спам',
        ]);
    }

    public function test_submit_returns_403_when_public_submit_disabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revoff');
        $host = $this->tenancyHostForSlug('revoff');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.form_show_rating', '');

        $this->postJson('http://'.$host.'/api/tenant/reviews/submit', [
            'name' => 'Тест',
            'body' => str_repeat('д', 25),
            'consent' => '1',
            'page_url' => '/otzyvy',
        ])->assertStatus(403);
    }

    public function test_reviews_page_shows_cta_when_submit_enabled(): void
    {
        $tenant = $this->createTenantWithActiveDomain('revcta');
        $host = $this->tenancyHostForSlug('revcta');
        TenantSetting::setForTenant((int) $tenant->id, 'reviews.public_submit_enabled', '1');

        $this->call('GET', 'http://'.$host.'/reviews')
            ->assertOk()
            ->assertSee('Оставить отзыв', false);
    }
}
