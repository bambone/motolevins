<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\CalendarEventLinkStatus;
use App\Scheduling\Enums\CalendarSyncDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEventLink extends Model
{
    protected $table = 'calendar_event_links';

    protected $fillable = [
        'calendar_subscription_id',
        'scheduling_resource_id',
        'linkable_type',
        'linkable_id',
        'external_calendar_id',
        'external_event_id',
        'external_event_uid',
        'provider_etag',
        'sync_direction',
        'link_status',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'sync_direction' => CalendarSyncDirection::class,
            'link_status' => CalendarEventLinkStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function calendarSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'calendar_subscription_id');
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
