<?php

declare(strict_types=1);

namespace App\BookingConsent;

use App\Models\TenantBookingConsentItem;
use Illuminate\Support\Collection;

final class TenantBookingConsentQuery
{
    /**
     * @return Collection<int, TenantBookingConsentItem>
     */
    public function enabledOrdered(int $tenantId): Collection
    {
        return TenantBookingConsentItem::query()
            ->where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
