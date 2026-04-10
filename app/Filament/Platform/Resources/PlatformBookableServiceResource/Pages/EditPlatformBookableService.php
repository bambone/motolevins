<?php

namespace App\Filament\Platform\Resources\PlatformBookableServiceResource\Pages;

use App\Filament\Platform\Resources\PlatformBookableServiceResource;
use Filament\Resources\Pages\EditRecord;

class EditPlatformBookableService extends EditRecord
{
    protected static string $resource = PlatformBookableServiceResource::class;

    public function getTitle(): string
    {
        return 'Редактировать услугу';
    }
}
