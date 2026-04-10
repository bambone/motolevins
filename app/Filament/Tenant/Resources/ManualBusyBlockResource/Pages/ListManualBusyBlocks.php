<?php

namespace App\Filament\Tenant\Resources\ManualBusyBlockResource\Pages;

use App\Filament\Tenant\Resources\ManualBusyBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManualBusyBlocks extends ListRecords
{
    protected static string $resource = ManualBusyBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
