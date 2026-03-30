<?php

namespace Tests\Feature\Terminology;

use App\Models\Tenant;
use App\Terminology\DomainTermKeys;
use App\Terminology\TenantTerminologyService;
use Database\Seeders\DomainTerminologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantTerminologySeededLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_vocabulary_never_surfaces_raw_term_key_as_label(): void
    {
        if (! Schema::hasTable('domain_terms') || ! Schema::hasTable('domain_localization_presets')) {
            $this->markTestSkipped('Terminology tables missing');
        }

        if (DB::table('domain_terms')->count() === 0) {
            (new DomainTerminologySeeder)->run();
        }

        $genericId = DB::table('domain_localization_presets')->where('slug', 'generic_services')->value('id');
        $this->assertNotNull($genericId);

        $tenant = Tenant::query()->create([
            'name' => 'L',
            'slug' => 'l-'.uniqid(),
            'status' => 'active',
            'domain_localization_preset_id' => $genericId,
        ]);

        app(TenantTerminologyService::class)->forgetTenant($tenant->id);

        $svc = app(TenantTerminologyService::class);

        foreach (DomainTermKeys::all() as $key) {
            $label = $svc->label($tenant, $key);
            $this->assertNotSame(
                $key,
                $label,
                'Raw term_key must not be used as display label for '.$key
            );
        }
    }
}
