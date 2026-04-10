<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\AvailabilityRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityRule extends Model
{
    protected $table = 'availability_rules';

    protected $fillable = [
        'scheduling_resource_id',
        'applies_to_scheduling_target_id',
        'applies_to_bookable_service_id',
        'rule_type',
        'weekday',
        'starts_at_local',
        'ends_at_local',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => AvailabilityRuleType::class,
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function schedulingResource(): BelongsTo
    {
        return $this->belongsTo(SchedulingResource::class, 'scheduling_resource_id');
    }

    public function appliesToSchedulingTarget(): BelongsTo
    {
        return $this->belongsTo(SchedulingTarget::class, 'applies_to_scheduling_target_id');
    }

    public function appliesToBookableService(): BelongsTo
    {
        return $this->belongsTo(BookableService::class, 'applies_to_bookable_service_id');
    }
}
