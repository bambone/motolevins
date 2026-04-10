<?php

namespace App\Filament\Tenant\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Tenant\Resources\AvailabilityExceptionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAvailabilityException extends CreateRecord
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
