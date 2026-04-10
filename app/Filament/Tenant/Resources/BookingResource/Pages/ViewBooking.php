<?php

namespace App\Filament\Tenant\Resources\BookingResource\Pages;

use App\ContactChannels\LeadContactActionResolver;
use App\Filament\Tenant\Resources\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        if (! $record instanceof Booking) {
            return [];
        }

        $resolver = app(LeadContactActionResolver::class);
        $descriptors = $resolver->orderedActionsForBooking($record);
        $actions = [];
        foreach ($descriptors as $d) {
            $type = (string) ($d['type'] ?? '');
            if ($type === '') {
                continue;
            }
            $actions[] = Action::make('booking_cc_'.$type)
                ->label($d['label'] ?? $type)
                ->icon($d['icon'] ?? 'heroicon-o-link')
                ->color($d['color'] ?? 'gray')
                ->url($d['url'] ?? '#')
                ->openUrlInNewTab((bool) ($d['open_in_new_tab'] ?? false));
        }

        return $actions;
    }
}
