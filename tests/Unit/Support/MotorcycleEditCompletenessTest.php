<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Motorcycle;
use App\Support\Motorcycle\MotorcycleEditCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class MotorcycleEditCompletenessTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_worst_status_orders_todo_then_warn_then_ok(): void
    {
        $this->assertSame(MotorcycleEditCompleteness::STATUS_OK, MotorcycleEditCompleteness::worst([]));
        $this->assertSame(MotorcycleEditCompleteness::STATUS_OK, MotorcycleEditCompleteness::worst([
            MotorcycleEditCompleteness::STATUS_OK,
        ]));
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, MotorcycleEditCompleteness::worst([
            MotorcycleEditCompleteness::STATUS_OK,
            MotorcycleEditCompleteness::STATUS_WARN,
        ]));
        $this->assertSame(MotorcycleEditCompleteness::STATUS_TODO, MotorcycleEditCompleteness::worst([
            MotorcycleEditCompleteness::STATUS_OK,
            MotorcycleEditCompleteness::STATUS_WARN,
            MotorcycleEditCompleteness::STATUS_TODO,
        ]));
    }

    public function test_checklist_marks_missing_core_fields_as_todo_or_warn(): void
    {
        $tenant = $this->createTenantWithActiveDomain('unit_moto_complete');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => '',
            'slug' => '',
            'status' => 'hidden',
            'show_in_catalog' => false,
            'short_description' => null,
            'full_description' => null,
            'price_per_day' => 1000,
        ]);

        $items = MotorcycleEditCompleteness::checklistItems($m->fresh());
        $byKey = [];
        foreach ($items as $row) {
            $byKey[$row['key']] = $row['status'];
        }

        $this->assertSame(MotorcycleEditCompleteness::STATUS_TODO, $byKey['name']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_TODO, $byKey['slug']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_TODO, $byKey['cover']);
        $this->assertArrayHasKey('tariffs', $byKey);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, $byKey['short_description']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, $byKey['full_description']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, $byKey['seo_title']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, $byKey['seo_description']);
        $this->assertSame(MotorcycleEditCompleteness::STATUS_WARN, $byKey['publication']);
    }

    public function test_toc_sections_reference_stable_anchor_ids(): void
    {
        $tenant = $this->createTenantWithActiveDomain('unit_moto_toc');
        $m = Motorcycle::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Toc',
            'slug' => 'toc',
            'status' => 'available',
            'show_in_catalog' => true,
            'price_per_day' => 1000,
        ]);

        $sections = MotorcycleEditCompleteness::tocSections($m);
        $ids = array_map(fn (array $s): string => $s['id'], $sections);

        $this->assertSame(
            ['moto-main', 'moto-pricing', 'moto-media', 'moto-page', 'moto-specs', 'moto-desc', 'moto-seo'],
            $ids,
        );
    }
}
