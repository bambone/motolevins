<?php

namespace App\Filament\Tenant\Resources\CalendarConnectionResource\Pages;

use App\Filament\Tenant\Resources\CalendarConnectionResource;
use Filament\Resources\Pages\EditRecord;

class EditCalendarConnection extends EditRecord
{
    protected static string $resource = CalendarConnectionResource::class;

    /**
     * Пустое поле секретов не затирает уже сохранённые учётные данные.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! array_key_exists('credentials_encrypted', $data)) {
            return $data;
        }
        if (trim((string) $data['credentials_encrypted']) === '') {
            unset($data['credentials_encrypted']);
        }

        return $data;
    }
}
