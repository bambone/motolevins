<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantLocationResource\Pages;

use App\Filament\Tenant\Resources\TenantLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantLocations extends ListRecords
{
    protected static string $resource = TenantLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
