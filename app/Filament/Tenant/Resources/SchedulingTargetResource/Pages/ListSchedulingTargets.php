<?php

namespace App\Filament\Tenant\Resources\SchedulingTargetResource\Pages;

use App\Filament\Tenant\Resources\SchedulingTargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchedulingTargets extends ListRecords
{
    protected static string $resource = SchedulingTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
