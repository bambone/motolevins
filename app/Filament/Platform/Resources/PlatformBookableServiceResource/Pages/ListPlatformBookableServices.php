<?php

namespace App\Filament\Platform\Resources\PlatformBookableServiceResource\Pages;

use App\Filament\Platform\Resources\PlatformBookableServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformBookableServices extends ListRecords
{
    protected static string $resource = PlatformBookableServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
