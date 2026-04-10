<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\BookableServiceLinkType;
use App\Scheduling\Enums\CalendarUsageMode;
use App\Scheduling\Enums\OccupancyScopeMode;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\SchedulingTargetType;
use App\Scheduling\RentalUnitSchedulingLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookableService extends Model
{
    protected $table = 'bookable_services';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'motorcycle_id',
        'rental_unit_id',
        'slug',
        'title',
        'description',
        'duration_minutes',
        'slot_step_minutes',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'min_booking_notice_minutes',
        'max_booking_horizon_days',
        'requires_confirmation',
        'default_write_calendar_subscription_id',
        'is_active',
        'sort_weight',
        'sync_title_from_source',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'requires_confirmation' => 'boolean',
            'is_active' => 'boolean',
            'sync_title_from_source' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function rentalUnit(): BelongsTo
    {
        return $this->belongsTo(RentalUnit::class);
    }

    public function defaultWriteSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'default_write_calendar_subscription_id');
    }

    public function schedulingTarget(): HasOne
    {
        $scope = $this->scheduling_scope instanceof SchedulingScope
            ? $this->scheduling_scope->value
            : $this->scheduling_scope;

        return $this->hasOne(SchedulingTarget::class, 'target_id')
            ->where('scheduling_targets.target_type', SchedulingTargetType::BookableService->value)
            ->where('scheduling_targets.scheduling_scope', $scope)
            ->where('scheduling_targets.tenant_id', $this->tenant_id);
    }

    public function linkType(): BookableServiceLinkType
    {
        if ($this->motorcycle_id !== null && $this->rental_unit_id === null) {
            return BookableServiceLinkType::Motorcycle;
        }
        if ($this->rental_unit_id !== null && $this->motorcycle_id === null) {
            return BookableServiceLinkType::RentalUnit;
        }

        return BookableServiceLinkType::Standalone;
    }

    public function isStandalone(): bool
    {
        return $this->linkType() === BookableServiceLinkType::Standalone;
    }

    public function isMotorcycleLinked(): bool
    {
        return $this->linkType() === BookableServiceLinkType::Motorcycle;
    }

    public function isRentalUnitLinked(): bool
    {
        return $this->linkType() === BookableServiceLinkType::RentalUnit;
    }

    public function resolvedMotorcycle(): ?Motorcycle
    {
        if ($this->isMotorcycleLinked()) {
            return $this->motorcycle;
        }
        if ($this->isRentalUnitLinked()) {
            $this->loadMissing('rentalUnit.motorcycle');

            return $this->rentalUnit?->motorcycle;
        }

        return null;
    }

    public function bindingLabel(): string
    {
        if ($this->isStandalone()) {
            return '—';
        }
        if ($this->isMotorcycleLinked()) {
            return $this->loadMissing('motorcycle')->motorcycle?->name ?? '—';
        }
        $u = $this->loadMissing('rentalUnit.motorcycle')->rentalUnit;

        return $u !== null ? RentalUnitSchedulingLabel::label($u) : '—';
    }

    protected static function booted(): void
    {
        static::saving(function (BookableService $service): void {
            $m = $service->motorcycle_id;
            $r = $service->rental_unit_id;
            if ($m !== null && $r !== null) {
                throw new \InvalidArgumentException('BookableService cannot set both motorcycle_id and rental_unit_id.');
            }
            if ($m !== null && $service->tenant_id !== null) {
                $ok = Motorcycle::query()->whereKey($m)->where('tenant_id', $service->tenant_id)->exists();
                if (! $ok) {
                    throw new \InvalidArgumentException('motorcycle_id must reference a motorcycle in the same tenant.');
                }
            }
            if ($r !== null && $service->tenant_id !== null) {
                $ok = RentalUnit::query()->whereKey($r)->where('tenant_id', $service->tenant_id)->exists();
                if (! $ok) {
                    throw new \InvalidArgumentException('rental_unit_id must reference a rental unit in the same tenant.');
                }
            }
        });

        static::created(function (BookableService $service): void {
            SchedulingTarget::query()->firstOrCreate(
                [
                    'scheduling_scope' => $service->scheduling_scope,
                    'tenant_id' => $service->tenant_id,
                    'target_type' => SchedulingTargetType::BookableService,
                    'target_id' => $service->id,
                ],
                [
                    'label' => $service->title,
                    'scheduling_enabled' => false,
                    'external_busy_enabled' => false,
                    'internal_busy_enabled' => true,
                    'auto_write_to_calendar_enabled' => false,
                    'occupancy_scope_mode' => OccupancyScopeMode::Generic,
                    'calendar_usage_mode' => CalendarUsageMode::Disabled,
                    'is_active' => true,
                ]
            );
        });
    }
}
