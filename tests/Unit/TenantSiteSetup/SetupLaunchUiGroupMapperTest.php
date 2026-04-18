<?php

namespace Tests\Unit\TenantSiteSetup;

use App\TenantSiteSetup\SetupLaunchUiGroupMapper;
use PHPUnit\Framework\TestCase;

class SetupLaunchUiGroupMapperTest extends TestCase
{
    public function test_booking_notifications_brief_maps_to_launch_polish(): void
    {
        $this->assertSame(
            SetupLaunchUiGroupMapper::LAUNCH_POLISH,
            SetupLaunchUiGroupMapper::uiGroupForItemKey('setup.booking_notifications_brief'),
        );
    }
}
