<?php

namespace App\Filament\Tenant\Resources\BookingResource\Pages;

use App\Filament\Tenant\Forms\ManualOperatorBookingForm;
use App\Filament\Tenant\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ManualOperatorBookingForm::standaloneBookingCreateAction(
                afterSubmit: function (): void {
                    $this->dispatch('$refresh');
                },
            ),
        ];
    }
}
