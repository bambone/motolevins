<?php

namespace App\Filament\Tenant\Resources\ReviewResource\Pages;

use App\Filament\Tenant\Resources\ReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! isset($data['rating']) || $data['rating'] === '' || $data['rating'] === null) {
            $data['rating'] = null;
        } else {
            $data['rating'] = max(1, min(5, (int) $data['rating']));
        }

        return $data;
    }
}
