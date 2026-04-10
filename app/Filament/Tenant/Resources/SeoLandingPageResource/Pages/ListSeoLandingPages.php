<?php

namespace App\Filament\Tenant\Resources\SeoLandingPageResource\Pages;

use App\Filament\Tenant\Resources\SeoLandingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoLandingPages extends ListRecords
{
    protected static string $resource = SeoLandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
