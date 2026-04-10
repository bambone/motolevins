<?php

namespace App\Filament\Tenant\Resources\SchedulingResourceResource\Pages;

use App\Filament\Tenant\Resources\SchedulingResourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchedulingResources extends ListRecords
{
    protected static string $resource = SchedulingResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
