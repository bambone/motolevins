<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\MatchConfidence;
use App\Scheduling\Enums\MatchMode;
use App\Scheduling\Enums\OccupancyMappingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarOccupancyMapping extends Model
{
    protected $table = 'calendar_occupancy_mappings';

    protected $fillable = [
        'calendar_subscription_id',
        'mapping_type',
        'scheduling_target_id',
        'scheduling_resource_id',
        'match_mode',
        'match_confidence',
        'rules_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'mapping_type' => OccupancyMappingType::class,
            'match_mode' => MatchMode::class,
            'match_confidence' => MatchConfidence::class,
            'rules_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function calendarSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'calendar_subscription_id');
    }

    public function schedulingTarget(): BelongsTo
    {
        return $this->belongsTo(SchedulingTarget::class, 'scheduling_target_id');
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }
}
