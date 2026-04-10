<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\ExternalBusyEffect;
use App\Scheduling\Enums\OccupancyScopeMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\Enums\StaleBusyPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchedulingTarget extends Model
{
    protected $table = 'scheduling_targets';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'target_type',
        'target_id',
        'label',
        'scheduling_enabled',
        'external_busy_enabled',
        'internal_busy_enabled',
        'auto_write_to_calendar_enabled',
        'occupancy_scope_mode',
        'calendar_usage_mode',
        'external_busy_effect',
        'stale_busy_policy',
        'default_write_calendar_subscription_id',
        'is_active',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'target_type' => SchedulingTargetType::class,
            'scheduling_enabled' => 'boolean',
            'external_busy_enabled' => 'boolean',
            'internal_busy_enabled' => 'boolean',
            'auto_write_to_calendar_enabled' => 'boolean',
            'occupancy_scope_mode' => OccupancyScopeMode::class,
            'calendar_usage_mode' => CalendarUsageMode::class,
            'external_busy_effect' => ExternalBusyEffect::class,
            'stale_busy_policy' => StaleBusyPolicy::class,
            'is_active' => 'boolean',
            'settings_json' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function schedulingResources(): BelongsToMany
    {
        return $this->belongsToMany(
            SchedulingResource::class,
            'scheduling_target_resource',
            'scheduling_target_id',
            'scheduling_resource_id'
        )->withPivot(['priority', 'is_default', 'assignment_strategy'])->withTimestamps();
    }

    public function defaultWriteSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'default_write_calendar_subscription_id');
    }

    public function resolvedTarget(): ?Model
    {
        if ($this->target_id === null) {
            return null;
        }

        return match ($this->target_type) {
            SchedulingTargetType::BookableService => BookableService::query()->find($this->target_id),
            SchedulingTargetType::RentalUnit => RentalUnit::query()->find($this->target_id),
            default => null,
        };
    }
}
