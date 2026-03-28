<?php

namespace App\Filament\Tenant\Resources\CustomDomainResource\Pages;

use App\Filament\Tenant\Resources\CustomDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomDomains extends ListRecords
{
    protected static string $resource = CustomDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
