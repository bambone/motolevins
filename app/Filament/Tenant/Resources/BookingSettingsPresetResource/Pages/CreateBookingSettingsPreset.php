<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookingSettingsPresetResource\Pages;

use App\Filament\Tenant\Resources\BookingSettingsPresetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingSettingsPreset extends CreateRecord
{
    protected static string $resource = BookingSettingsPresetResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = currentTenant();
        $data['tenant_id'] = $tenant?->id;

        return BookingSettingsPresetResource::foldPayloadFormFieldsIntoPayload($data);
    }
}
