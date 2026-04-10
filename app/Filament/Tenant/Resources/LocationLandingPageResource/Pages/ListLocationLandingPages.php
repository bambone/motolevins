<?php

namespace App\Filament\Tenant\Resources\LocationLandingPageResource\Pages;

use App\Filament\Tenant\Resources\LocationLandingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocationLandingPages extends ListRecords
{
    protected static string $resource = LocationLandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
