<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookingSettingsPresetResource\Pages;

use App\Filament\Tenant\Resources\BookingSettingsPresetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookingSettingsPresets extends ListRecords
{
    protected static string $resource = BookingSettingsPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
