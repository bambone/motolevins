<?php

namespace App\Filament\Platform\Resources\PlatformBookableServiceResource\Pages;

use App\Filament\Platform\Resources\PlatformBookableServiceResource;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;

class CreatePlatformBookableService extends CreateRecord
{
    protected static string $resource = PlatformBookableServiceResource::class;

    public function getTitle(): string
    {
        return 'Создать услугу';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Platform;
        $data['tenant_id'] = null;

        return $data;
    }
}
