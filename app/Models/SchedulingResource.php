<?php

declare(strict_types=1);

namespace App\Models;

use App\Scheduling\Enums\SchedulingResourceType;
use App\Scheduling\Enums\SchedulingScope;
use App\Scheduling\Enums\TentativeEventsPolicy;
use App\Scheduling\Enums\UnconfirmedRequestsPolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulingResource extends Model
{
    protected $table = 'scheduling_resources';

    protected $fillable = [
        'scheduling_scope',
        'tenant_id',
        'resource_type',
        'user_id',
        'label',
        'timezone',
        'tentative_events_policy',
        'unconfirmed_requests_policy',
        'default_write_calendar_subscription_id',
        'is_active',
        'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_scope' => SchedulingScope::class,
            'tentative_events_policy' => TentativeEventsPolicy::class,
            'unconfirmed_requests_policy' => UnconfirmedRequestsPolicy::class,
            'is_active' => 'boolean',
            'settings_json' => 'array',
        ];
    }

    /**
     * В БД хранится строковый код (встроенный enum или пользовательский slug до 32 символов).
     */
    protected function resourceType(): Attribute
    {
        return Attribute::make(
            set: static function (mixed $value): string {
                if ($value instanceof SchedulingResourceType) {
                    return $value->value;
                }

                return is_string($value) ? substr($value, 0, 32) : substr((string) $value, 0, 32);
            },
        );
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedulingTargets(): BelongsToMany
    {
        return $this->belongsToMany(
            SchedulingTarget::class,
            'scheduling_target_resource',
            'scheduling_resource_id',
            'scheduling_target_id'
        )->withPivot(['priority', 'is_default', 'assignment_strategy'])->withTimestamps();
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class, 'scheduling_resource_id');
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class, 'scheduling_resource_id');
    }

    public function calendarConnections(): HasMany
    {
        return $this->hasMany(CalendarConnection::class, 'scheduling_resource_id');
    }

    public function defaultWriteSubscription(): BelongsTo
    {
        return $this->belongsTo(CalendarSubscription::class, 'default_write_calendar_subscription_id');
    }
}
