<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

use App\Models\NotificationDestination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssertNotificationSubscriptionDestinations
{
    /**
     * @param  list<int|string>  $ids
     */
    public static function forTenantForm(array $ids): void
    {
        $ids = AssertTenantOwnedIds::normalizePositiveIntIds($ids);
        if ($ids === []) {
            return;
        }

        $tenant = currentTenant();
        if ($tenant === null) {
            throw ValidationException::withMessages([
                'destination_ids' => 'Контекст клиента не найден.',
            ]);
        }

        AssertTenantOwnedIds::assertIntIdsBelongToScopedQuery(
            NotificationDestination::class,
            $ids,
            function (Builder $q) use ($tenant): void {
                $q->where('tenant_id', $tenant->id);
                if (! Gate::allows('manage_notifications')) {
                    $q->where('user_id', Auth::id());
                }
            },
            'destination_ids',
            'Некорректные получатели.',
        );
    }
}
