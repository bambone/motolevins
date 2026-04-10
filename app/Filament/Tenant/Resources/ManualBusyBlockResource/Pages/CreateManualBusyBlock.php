<?php

namespace App\Filament\Tenant\Resources\ManualBusyBlockResource\Pages;

use App\Filament\Tenant\Resources\ManualBusyBlockResource;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateManualBusyBlock extends CreateRecord
{
    protected static string $resource = ManualBusyBlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
