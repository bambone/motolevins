<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

use App\Models\BookableService;
use App\Models\CalendarSubscription;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\SchedulingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Серверные проверки выбранных ID для tenant-scoped моделей (защита от подмены payload в Livewire).
 */
final class AssertTenantOwnedIds
{
    /**
     * @param  list<int|string|null>  $rawIds
     * @param  callable(Builder): void  $scope  Ограничить запрос текущим клиентом / scope.
     */
    public static function assertIntIdsBelongToScopedQuery(
        string $modelClass,
        array $rawIds,
        callable $scope,
        string $validationAttribute = 'data',
        string $message = 'Некорректное значение.',
    ): void {
        $ids = self::normalizePositiveIntIds($rawIds);
        if ($ids === []) {
            return;
        }

        self::requireTenant($validationAttribute);

        /** @var Builder $query */
        $query = $modelClass::query();
        $scope($query);

        if ($query->whereIn((new $modelClass)->getKeyName(), $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages([
                $validationAttribute => $message,
            ]);
        }
    }

    public static function assertOptionalIntIdBelongsToScopedQuery(
        mixed $rawId,
        string $modelClass,
        callable $scope,
        string $validationAttribute = 'data',
        string $message = 'Некорректное значение.',
    ): void {
        if ($rawId === null || $rawId === '') {
            return;
        }
        $id = (int) $rawId;
        if ($id <= 0) {
            throw ValidationException::withMessages([
                $validationAttribute => $message,
            ]);
        }
        self::assertIntIdsBelongToScopedQuery($modelClass, [$id], $scope, $validationAttribute, $message);
    }

    /**
     * @param  list<int|string>  $ids
     */
    public static function assertSchedulingResourcesForCurrentTenant(array $ids, string $validationAttribute = 'schedulingResources'): void
    {
        $tenant = self::requireTenant($validationAttribute);
        self::assertIntIdsBelongToScopedQuery(
            SchedulingResource::class,
            $ids,
            function (Builder $q) use ($tenant): void {
                $q->where('scheduling_scope', SchedulingScope::Tenant)
                    ->where('tenant_id', $tenant->id);
            },
            $validationAttribute,
            'Некорректные ресурсы расписания.',
        );
    }

    public static function assertOptionalSchedulingResourceId(mixed $rawId, string $validationAttribute = 'scheduling_resource_id'): void
    {
        $tenant = self::requireTenant($validationAttribute);
        self::assertOptionalIntIdBelongsToScopedQuery(
            $rawId,
            SchedulingResource::class,
            function (Builder $q) use ($tenant): void {
                $q->where('scheduling_scope', SchedulingScope::Tenant)
                    ->where('tenant_id', $tenant->id);
            },
            $validationAttribute,
        );
    }

    public static function assertOptionalSchedulingTargetId(mixed $rawId, string $validationAttribute = 'scheduling_target_id'): void
    {
        $tenant = self::requireTenant($validationAttribute);
        self::assertOptionalIntIdBelongsToScopedQuery(
            $rawId,
            SchedulingTarget::class,
            function (Builder $q) use ($tenant): void {
                $q->where('scheduling_scope', SchedulingScope::Tenant)
                    ->where('tenant_id', $tenant->id);
            },
            $validationAttribute,
        );
    }

    public static function assertOptionalBookableServiceId(mixed $rawId, string $validationAttribute = 'bookable_service_id'): void
    {
        $tenant = self::requireTenant($validationAttribute);
        self::assertOptionalIntIdBelongsToScopedQuery(
            $rawId,
            BookableService::class,
            function (Builder $q) use ($tenant): void {
                $q->where('scheduling_scope', SchedulingScope::Tenant)
                    ->where('tenant_id', $tenant->id);
            },
            $validationAttribute,
        );
    }

    public static function assertCalendarSubscriptionForCurrentTenant(mixed $rawId, string $validationAttribute = 'calendar_subscription_id'): void
    {
        $tenant = self::requireTenant($validationAttribute);
        $id = (int) $rawId;
        if ($id <= 0) {
            throw ValidationException::withMessages([
                $validationAttribute => 'Некорректная подписка на календарь.',
            ]);
        }
        self::assertIntIdsBelongToScopedQuery(
            CalendarSubscription::class,
            [$id],
            function (Builder $q) use ($tenant): void {
                $q->whereHas('calendarConnection', function (Builder $c) use ($tenant): void {
                    $c->where('scheduling_scope', SchedulingScope::Tenant)
                        ->where('tenant_id', $tenant->id);
                });
            },
            $validationAttribute,
            'Некорректная подписка на календарь.',
        );
    }

    /**
     * @param  list<int|string|null>  $rawIds
     * @return list<int>
     */
    public static function normalizePositiveIntIds(array $rawIds): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $v): int => (int) $v, $rawIds),
            static fn (int $id): bool => $id > 0,
        )));
    }

    private static function requireTenant(string $validationAttribute): Tenant
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            throw ValidationException::withMessages([
                $validationAttribute => 'Контекст клиента не найден.',
            ]);
        }

        return $tenant;
    }
}
