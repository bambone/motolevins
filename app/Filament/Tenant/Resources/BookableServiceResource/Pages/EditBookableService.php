<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BookableServiceResource\Pages;

use App\Filament\Tenant\Resources\BookableServiceResource;
use Filament\Resources\Pages\EditRecord;

class EditBookableService extends EditRecord
{
    protected static string $resource = BookableServiceResource::class;

    public function getTitle(): string
    {
        return 'Редактировать услугу';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['motorcycle_id'], $data['rental_unit_id']);

        return parent::mutateFormDataBeforeSave($data);
    }
}
