<?php

declare(strict_types=1);

namespace Tests\Unit\TenantSiteSetup;

use App\TenantSiteSetup\SetupTrackReasonCatalog;
use PHPUnit\Framework\TestCase;

class SetupTrackReasonCatalogTest extends TestCase
{
    public function test_scheduling_module_disabled_has_expected_copy(): void
    {
        $catalog = new SetupTrackReasonCatalog;
        $r = $catalog->forCode('scheduling_module_disabled');
        $this->assertNotNull($r);
        $this->assertStringContainsString('расписан', mb_strtolower($r->title));
        $this->assertNotSame('', $r->body);
    }

    public function test_unknown_code_uses_fallback(): void
    {
        $catalog = new SetupTrackReasonCatalog;
        $r = $catalog->forCodeOrFallback('unknown_code_xyz');
        $this->assertStringContainsString('unknown_code_xyz', $r->body);
    }
}
