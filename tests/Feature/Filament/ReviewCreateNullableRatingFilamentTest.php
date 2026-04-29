<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Tenant\Resources\ReviewResource\Pages\CreateReview;
use App\Models\User;
use App\Services\CurrentTenantManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class ReviewCreateNullableRatingFilamentTest extends TestCase
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

    public function test_create_with_empty_rating_saves_null(): void
    {
        $tenant = $this->createTenantWithActiveDomain('fil_rev_rating_null');
        $user = User::factory()->create(['status' => 'active']);
        $user->tenants()->attach($tenant->id, ['role' => 'tenant_owner', 'status' => 'active']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
        app(CurrentTenantManager::class)->setTenant($tenant);

        Livewire::test(CreateReview::class)
            ->fillForm([
                'name' => 'Автор без звёзд',
                'body' => str_repeat('Текст отзыва для формы. ', 3),
                'status' => 'draft',
                'rating' => '',
                'media_type' => 'text',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'tenant_id' => $tenant->id,
            'name' => 'Автор без звёзд',
            'rating' => null,
        ]);
    }
}
