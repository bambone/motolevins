<?php

declare(strict_types=1);

namespace App\Scheduling\Calendar;

use App\Models\CalendarEventLink;

/**
 * Product matrix for outbound calendar rows: trigger → action on {@see CalendarEventLink}.
 *
 * Actions: delete_remote, update_remote, leave_remote_unchanged, append_note, detach_orphan.
 * Implementation can be phased: start with delete/update on CRM cancel and reschedule.
 */
final class ExternalCalendarEventLifecycle
{
    public const ACTION_DELETE_REMOTE = 'delete_remote';

    public const ACTION_UPDATE_REMOTE = 'update_remote';

    public const ACTION_LEAVE = 'leave_remote_unchanged';

    public const ACTION_APPEND_NOTE = 'append_note';

    public const ACTION_DETACH_ORPHAN = 'detach_orphan';

    /**
     * @return list<string> ordered preferred actions for a CRM cancellation.
     */
    public static function onCrmRequestCancelled(CalendarEventLink $link): array
    {
        return [self::ACTION_DELETE_REMOTE, self::ACTION_DETACH_ORPHAN];
    }

    /**
     * @return list<string>
     */
    public static function onSlotRescheduled(CalendarEventLink $link): array
    {
        return [self::ACTION_UPDATE_REMOTE];
    }

    /**
     * @return list<string>
     */
    public static function onResourceReassigned(CalendarEventLink $link): array
    {
        return [self::ACTION_UPDATE_REMOTE, self::ACTION_DELETE_REMOTE, self::ACTION_DETACH_ORPHAN];
    }

    /**
     * @return list<string>
     */
    public static function onHoldExpired(CalendarEventLink $link): array
    {
        return [self::ACTION_DELETE_REMOTE, self::ACTION_DETACH_ORPHAN];
    }
}
