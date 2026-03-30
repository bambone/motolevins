<?php

namespace Tests\Feature\Migrations;

use App\Terminology\DomainTermKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DomainTerminologyNewPresetsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_presets_exist_with_full_preset_terms_and_migration_is_idempotent(): void
    {
        $expected = count(DomainTermKeys::all());
        $m120000 = require database_path('migrations/2026_03_30_120000_create_domain_terminology_tables.php');
        $m120000->up();

        $m120001 = require database_path('migrations/2026_03_30_120001_backfill_tenant_domain_localization_presets.php');
        $m120001->up();

        $m120002 = require database_path('migrations/2026_03_30_120002_seed_advanced_driving_and_nail_terminology_presets.php');
        $m120002->up();

        $this->assertPresetTerms('advanced_driving_pk', $expected);
        $this->assertPresetTerms('nail_service_booking', $expected);

        $this->assertDomainLabels();

        $countBefore = DB::table('domain_localization_preset_terms')->count();
        $m120002->up();
        $countAfter = DB::table('domain_localization_preset_terms')->count();
        $this->assertSame($countBefore, $countAfter);

        $this->assertPresetTerms('advanced_driving_pk', $expected);
        $this->assertPresetTerms('nail_service_booking', $expected);
    }

    private function assertPresetTerms(string $slug, int $expected): void
    {
        $presetId = DB::table('domain_localization_presets')->where('slug', $slug)->value('id');
        $this->assertNotNull($presetId, 'Preset '.$slug.' missing');

        $n = DB::table('domain_localization_preset_terms')->where('preset_id', $presetId)->count();
        $this->assertSame($expected, $n, 'Preset '.$slug.' should have '.$expected.' preset_terms');
    }

    private function assertDomainLabels(): void
    {
        $pid = DB::table('domain_localization_presets')->where('slug', 'advanced_driving_pk')->value('id');
        $tid = DB::table('domain_terms')->where('term_key', DomainTermKeys::RESOURCE)->value('id');
        $label = DB::table('domain_localization_preset_terms')
            ->where('preset_id', $pid)
            ->where('term_id', $tid)
            ->value('label');
        $this->assertSame('Курс', $label);

        $pidN = DB::table('domain_localization_presets')->where('slug', 'nail_service_booking')->value('id');
        $tidStaff = DB::table('domain_terms')->where('term_key', DomainTermKeys::STAFF_MEMBER)->value('id');
        $tidBook = DB::table('domain_terms')->where('term_key', DomainTermKeys::BOOKING)->value('id');
        $this->assertSame('Мастер', DB::table('domain_localization_preset_terms')
            ->where('preset_id', $pidN)->where('term_id', $tidStaff)->value('label'));
        $this->assertSame('Запись', DB::table('domain_localization_preset_terms')
            ->where('preset_id', $pidN)->where('term_id', $tidBook)->value('label'));
    }
}
