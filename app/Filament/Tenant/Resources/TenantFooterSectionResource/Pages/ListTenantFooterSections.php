<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantFooterSectionResource\Pages;

use App\Filament\Tenant\Resources\TenantFooterSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantFooterSections extends ListRecords
{
    protected static string $resource = TenantFooterSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
