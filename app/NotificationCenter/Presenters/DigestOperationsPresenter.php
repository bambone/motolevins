<?php

namespace App\NotificationCenter\Presenters;

use App\Models\CrmRequest;
use App\Models\Tenant;
use App\NotificationCenter\NotificationPayloadDto;
use Illuminate\Support\Carbon;

final class DigestOperationsPresenter
{
    public function dailyPayloadForTenant(Tenant $tenant, ?Carbon $day = null): NotificationPayloadDto
    {
        $day ??= Carbon::yesterday();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $newCount = CrmRequest::query()
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $closed = CrmRequest::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [CrmRequest::STATUS_CONVERTED, CrmRequest::STATUS_REJECTED, CrmRequest::STATUS_ARCHIVED])
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $body = sprintf(
            "Дата: %s\nНовых заявок: %d\nЗакрыто/архив: %d",
            $day->format('d.m.Y'),
            $newCount,
            $closed
        );

        return new NotificationPayloadDto(
            title: 'Сводка за день',
            body: $body,
            actionUrl: null,
            actionLabel: null,
            meta: [
                'day' => $day->toDateString(),
                'new_crm_requests' => $newCount,
                'closed_like' => $closed,
            ],
        );
    }
}
