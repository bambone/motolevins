<?php

namespace App\Filament\Platform\Resources\CrmRequestResource\Pages;

use App\Filament\Platform\Resources\CrmRequestResource;
use App\Models\CrmRequest;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewCrmRequest extends ViewRecord
{
    protected static string $resource = CrmRequestResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        /** @var CrmRequest $record */
        $record = static::getResource()::getEloquentQuery()
            ->with(['activities' => fn ($q) => $q->orderByDesc('created_at')])
            ->whereKey($key)
            ->firstOrFail();

        return $record;
    }
}
