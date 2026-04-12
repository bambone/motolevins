<?php

declare(strict_types=1);

namespace App\Tenant\Filament;

use App\Auth\AccessRoles;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Единственная поддерживаемая точка для списков пользователей в tenant-панели (селекты user_id, assignee и т.д.).
 * Не использует currentTenant() — всегда передавайте tenantId снаружи.
 *
 * Важно: методы со скоупом ({@see applyCabinetTeamScope}, {@see applyCabinetTeamScopeWithLegacyAssignee}) принимают
 * только {@see Builder} для модели {@see User} (например `User::query()` или relationship-билдер к `users`).
 * Не передавайте билдеры других моделей: внутри используются связь `tenants`, pivot `tenant_user` и
 * {@see Builder::getQualifiedKeyName()} относительно таблицы пользователей.
 */
final class TenantCabinetUserPicker
{
    /**
     * @param  Builder<User>  $usersQuery  только запрос к модели User
     */
    public static function applyCabinetTeamScope(Builder $usersQuery, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $usersQuery->whereRaw('0 = 1');

            return;
        }

        $usersQuery->whereHas('tenants', function (Builder $tq) use ($tenantId): void {
            $tq->where('tenants.id', $tenantId)
                ->where('tenant_user.status', 'active')
                ->whereIn('tenant_user.role', AccessRoles::tenantMembershipRolesForPanel());
        });
    }

    /**
     * Только для edit-формы: показать текущего назначенного пользователя, даже если он больше не в команде.
     * Не использовать при create и не использовать в серверной валидации.
     *
     * @param  Builder<User>  $usersQuery  только запрос к модели User (см. описание класса)
     */
    public static function applyCabinetTeamScopeWithLegacyAssignee(
        Builder $usersQuery,
        ?int $tenantId,
        ?int $assigneeUserId,
    ): void {
        if ($tenantId === null) {
            $usersQuery->whereRaw('0 = 1');

            return;
        }

        $usersQuery->where(function (Builder $inner) use ($tenantId, $assigneeUserId): void {
            $inner->whereHas('tenants', function (Builder $tq) use ($tenantId): void {
                $tq->where('tenants.id', $tenantId)
                    ->where('tenant_user.status', 'active')
                    ->whereIn('tenant_user.role', AccessRoles::tenantMembershipRolesForPanel());
            });
            if ($assigneeUserId !== null) {
                $inner->orWhere(
                    $inner->getModel()->getQualifiedKeyName(),
                    $assigneeUserId,
                );
            }
        });
    }

    public static function userBelongsToCabinetTeam(int $tenantId, int $userId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->whereHas('tenants', function (Builder $tq) use ($tenantId): void {
                $tq->where('tenants.id', $tenantId)
                    ->where('tenant_user.status', 'active')
                    ->whereIn('tenant_user.role', AccessRoles::tenantMembershipRolesForPanel());
            })
            ->exists();
    }

    public static function assertUserBelongsToCabinetTeam(
        int $tenantId,
        int $userId,
        string $errorAttributeKey = 'user_id',
    ): void {
        if (! self::userBelongsToCabinetTeam($tenantId, $userId)) {
            throw ValidationException::withMessages([
                $errorAttributeKey => 'Выбранный пользователь недоступен для текущего кабинета.',
            ]);
        }
    }

    /**
     * Список пользователей для нативного Select (id => имя). Всегда {@see User::query()}, без Filament relationship-query.
     *
     * @return array<int, string>
     */
    public static function nameOptionsForCabinet(?int $tenantId, ?int $legacyUserIdForEdit = null): array
    {
        if ($tenantId === null) {
            return [];
        }

        $query = User::query();
        if ($legacyUserIdForEdit !== null) {
            self::applyCabinetTeamScopeWithLegacyAssignee($query, $tenantId, $legacyUserIdForEdit);
        } else {
            self::applyCabinetTeamScope($query, $tenantId);
        }

        /** @var array<int, string> */
        return $query->orderBy('name')->pluck('name', 'id')->all();
    }
}
