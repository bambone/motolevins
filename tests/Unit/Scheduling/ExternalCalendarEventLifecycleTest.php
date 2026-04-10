<?php

declare(strict_types=1);

namespace Tests\Unit\Scheduling;

use App\Models\CalendarEventLink;
use App\Scheduling\Calendar\ExternalCalendarEventLifecycle;
use PHPUnit\Framework\TestCase;

final class ExternalCalendarEventLifecycleTest extends TestCase
{
    public function test_cancel_prefers_delete_then_detach(): void
    {
        $link = new CalendarEventLink;
        $this->assertSame(
            [
                ExternalCalendarEventLifecycle::ACTION_DELETE_REMOTE,
                ExternalCalendarEventLifecycle::ACTION_DETACH_ORPHAN,
            ],
            ExternalCalendarEventLifecycle::onCrmRequestCancelled($link),
        );
    }

    public function test_reschedule_prefers_update(): void
    {
        $link = new CalendarEventLink;
        $this->assertSame(
            [ExternalCalendarEventLifecycle::ACTION_UPDATE_REMOTE],
            ExternalCalendarEventLifecycle::onSlotRescheduled($link),
        );
    }

    public function test_hold_expired_prefers_delete_remote_then_detach_orphan(): void
    {
        $link = new CalendarEventLink;
        $this->assertSame(
            [
                ExternalCalendarEventLifecycle::ACTION_DELETE_REMOTE,
                ExternalCalendarEventLifecycle::ACTION_DETACH_ORPHAN,
            ],
            ExternalCalendarEventLifecycle::onHoldExpired($link),
        );
    }

    public function test_resource_reassigned_prefers_update_then_delete_then_detach(): void
    {
        $link = new CalendarEventLink;
        $this->assertSame(
            [
                ExternalCalendarEventLifecycle::ACTION_UPDATE_REMOTE,
                ExternalCalendarEventLifecycle::ACTION_DELETE_REMOTE,
                ExternalCalendarEventLifecycle::ACTION_DETACH_ORPHAN,
            ],
            ExternalCalendarEventLifecycle::onResourceReassigned($link),
        );
    }
}
