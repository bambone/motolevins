<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\ReviewImportSourceResource;
use App\Filament\Tenant\Resources\ReviewResource;
use App\Filament\Tenant\Resources\ReviewResource\Pages\EditReview;
use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviewImportCandidates;
use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviewImportSources;
use App\Filament\Tenant\Resources\ReviewResource\Pages\ListReviews;
use App\Models\Review;
use App\Models\ReviewImportSource;
use App\Models\User;
use App\Reviews\Import\ReviewImportSourceStatus;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class ReviewAdminSectionConsolidationFilamentTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Filament::setCurrentPanel(null);
        parent::tearDown();
    }

    public function test_review_import_source_resource_is_hidden_from_sidebar(): void
    {
        $this->assertFalse(ReviewImportSourceResource::shouldRegisterNavigation());
    }

    public function test_review_resource_registers_subsection_routes(): void
    {
        $this->assertTrue(Route::has('filament.admin.resources.reviews.index'));
        $this->assertTrue(Route::has('filament.admin.resources.reviews.import_sources'));
        $this->assertTrue(Route::has('filament.admin.resources.reviews.import_sources_create'));
        $this->assertTrue(Route::has('filament.admin.resources.reviews.import_sources_edit'));
        $this->assertTrue(Route::has('filament.admin.resources.reviews.import_candidates'));
    }

    public function test_list_reviews_livewire_shows_section_tabs(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_tabs');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(ListReviews::class)
            ->assertSee('Источники', false)
            ->assertSee('Кандидаты', false)
            ->assertSee('data-review-section-tabs', false);
    }

    public function test_edit_review_form_does_not_show_section_tabs(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_no_tabs');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $review = Review::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Автор',
            'body' => 'Текст для формы.',
            'status' => 'draft',
            'media_type' => 'text',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(EditReview::class, ['record' => $review->getKey()])
            ->assertDontSee('data-review-section-tabs');
    }

    public function test_review_import_source_resource_urls_delegate_to_review_nested_routes(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_imp_urls');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        app(CurrentTenantManager::class)->setTenant($tenant);

        $index = ReviewResource::getUrl('import_sources', [], false, 'admin', $tenant);
        $this->assertSame($index, ReviewImportSourceResource::getUrl(null, [], false, 'admin', $tenant));
        $this->assertSame($index, ReviewImportSourceResource::getUrl('index', [], false, 'admin', $tenant));

        $create = ReviewResource::getUrl('import_sources_create', [], false, 'admin', $tenant);
        $this->assertSame($create, ReviewImportSourceResource::getUrl('create', [], false, 'admin', $tenant));

        $source = ReviewImportSource::query()->withoutGlobalScopes()->create([
            'tenant_id' => (int) $tenant->id,
            'provider' => 'manual',
            'title' => 'T',
            'source_url' => 'https://example.com/r',
            'status' => ReviewImportSourceStatus::READY,
        ]);
        $edit = ReviewResource::getUrl('import_sources_edit', ['record' => $source], false, 'admin', $tenant);
        $this->assertSame($edit, ReviewImportSourceResource::getUrl('edit', ['record' => $source], false, 'admin', $tenant));
    }

    public function test_list_sources_uses_nested_urls_under_review_resource(): void
    {
        $this->assertSame(
            parse_url(ReviewResource::getUrl('import_sources', [], shouldGuessMissingParameters: false), PHP_URL_PATH),
            parse_url(ListReviewImportSources::getUrl(), PHP_URL_PATH),
        );
        $this->assertSame(
            parse_url(ReviewResource::getUrl('import_candidates', [], shouldGuessMissingParameters: false), PHP_URL_PATH),
            parse_url(ListReviewImportCandidates::getUrl(), PHP_URL_PATH),
        );
    }

    public function test_list_review_import_sources_page_uses_import_source_model_not_review(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_src_model');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        $component = Livewire::test(ListReviewImportSources::class)->instance();
        $this->assertSame(ReviewImportSource::class, $component->getModel());
    }

    public function test_edit_review_prefills_body_from_legacy_text_long_when_body_empty(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_legacy_body');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        $legacy = 'Старый полный текст до миграции в поле body.';

        $review = Review::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Клиент',
            'body' => null,
            'text' => null,
            'text_short' => null,
            'text_long' => $legacy,
            'status' => 'draft',
            'media_type' => 'text',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(EditReview::class, ['record' => $review->getKey()])
            ->assertFormSet([
                'body' => $legacy,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $review->refresh();
        $this->assertSame($legacy, $review->body);
    }
}
