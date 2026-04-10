<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookingSettingsPresetResource\Pages;

use App\Filament\Tenant\Resources\BookingSettingsPresetResource;
use App\Models\BookingSettingsPreset;
use Filament\Resources\Pages\EditRecord;

class EditBookingSettingsPreset extends EditRecord
{
    protected static string $resource = BookingSettingsPresetResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var BookingSettingsPreset $record */
        $record = $this->getRecord();

        return array_merge($data, BookingSettingsPresetResource::spreadPayloadToFormFields($record));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return BookingSettingsPresetResource::foldPayloadFormFieldsIntoPayload($data);
    }
}
