<?php

namespace App\Filament\Tenant\Resources\BookableServiceResource\Pages;

use App\Filament\Tenant\Resources\BookableServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookableServices extends ListRecords
{
    protected static string $resource = BookableServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
