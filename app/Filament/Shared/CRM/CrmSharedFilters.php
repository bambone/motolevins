<?php

namespace App\Filament\Shared\CRM;

use App\Models\CrmRequest;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class CrmSharedFilters
{
    /**
     * Distinct request_type values constrained by the same scope as the resource list (tenant / platform).
     *
     * @return array<string, string>
     */
    public static function requestTypeOptionsForScopedQuery(Builder $scopedCrmQuery): array
    {
        return (clone $scopedCrmQuery)
            ->whereNotNull('request_type')
            ->distinct()
            ->orderBy('request_type')
            ->pluck('request_type', 'request_type')
            ->all();
    }

    /**
     * @return array<int, SelectFilter>
     */
    public static function tableFilters(Builder $scopedCrmQuery): array
    {
        return [
            SelectFilter::make('status')
                ->label('Статус CRM')
                ->options(CrmRequest::statusLabels()),
            SelectFilter::make('request_type')
                ->label('Тип заявки')
                ->options(fn (): array => self::requestTypeOptionsForScopedQuery($scopedCrmQuery)),
        ];
    }
}
